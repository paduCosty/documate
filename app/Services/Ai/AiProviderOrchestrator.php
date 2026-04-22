<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates AI calls with exponential retry and fallback to a secondary provider.
 *
 * Flow:
 *   1. Try the primary provider up to $maxAttempts times (backoff: 1s → 2s → 4s …)
 *   2. If all attempts fail → try the fallback provider once
 *   3. If the fallback also fails → rethrow the primary exception
 *
 * Rate-limit errors (429) skip retries and go directly to fallback.
 */
class AiProviderOrchestrator implements AiProviderInterface
{
    public function __construct(
        private readonly AiProviderInterface  $primary,
        private readonly ?AiProviderInterface $fallback    = null,
        private readonly int                  $maxAttempts = 3,
        private readonly int                  $baseDelayMs = 1000,
    ) {}

    public function complete(AiRequest $request): AiResponse
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return $this->primary->complete($request);
            } catch (AiRateLimitException $e) {
                // Rate limit — no point retrying, go straight to fallback
                Log::warning("[orchestrator] Rate limit on \"{$this->primary->getName()}\". Going to fallback.", [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
                $lastException = $e;
                break;
            } catch (AiProviderException $e) {
                $lastException = $e;

                Log::warning("[orchestrator] Attempt {$attempt}/{$this->maxAttempts} failed on \"{$this->primary->getName()}\".", [
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxAttempts) {
                    $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
                    usleep($delayMs * 1000);
                }
            }
        }

        // All attempts on the primary provider failed
        if ($this->fallback !== null) {
            return $this->tryFallback($request, $lastException);
        }

        throw $lastException;
    }

    public function isAvailable(): bool
    {
        return $this->primary->isAvailable()
            || ($this->fallback !== null && $this->fallback->isAvailable());
    }

    public function getName(): string
    {
        return $this->primary->getName();
    }

    public function getDefaultModel(): string
    {
        return $this->primary->getDefaultModel();
    }

    public function getSupportedModels(): array
    {
        return $this->primary->getSupportedModels();
    }

    public function getPrimary(): AiProviderInterface
    {
        return $this->primary;
    }

    public function getFallback(): ?AiProviderInterface
    {
        return $this->fallback;
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function tryFallback(AiRequest $request, AiProviderException $primaryException): AiResponse
    {
        Log::info("[orchestrator] Switching to fallback provider \"{$this->fallback->getName()}\".", [
            'primary_error' => $primaryException->getMessage(),
        ]);

        try {
            $response = $this->fallback->complete($request);

            Log::info("[orchestrator] Fallback \"{$this->fallback->getName()}\" succeeded.");

            // Tag the response so callers know the fallback was used
            return new AiResponse(
                text:         $response->text,
                providerUsed: $response->providerUsed,
                modelUsed:    $response->modelUsed,
                inputTokens:  $response->inputTokens,
                outputTokens: $response->outputTokens,
                latencyMs:    $response->latencyMs,
                usedFallback: true,
            );
        } catch (AiProviderException $fallbackException) {
            Log::error("[orchestrator] Fallback \"{$this->fallback->getName()}\" also failed.", [
                'error' => $fallbackException->getMessage(),
            ]);

            // Rethrow the primary exception — it's more meaningful to the caller
            throw $primaryException;
        }
    }
}
