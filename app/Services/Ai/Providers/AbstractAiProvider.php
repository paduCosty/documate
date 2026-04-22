<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clasă abstractă cu utilități comune pentru toți providerii AI.
 *
 * Subclasele implementează doar:
 *   - buildPayload(AiRequest, string $model): array
 *   - parseResponse(array $body, string $provider, string $model, float $ms): AiResponse
 *   - getApiUrl(string $model): string
 *   - getHeaders(): array
 */
abstract class AbstractAiProvider implements AiProviderInterface
{
    public function complete(AiRequest $request): AiResponse
    {
        $model   = $request->model ?? $this->getDefaultModel();
        $url     = $this->getApiUrl($model);
        $payload = $this->buildPayload($request, $model);
        $timeout = (int) config("ai.providers.{$this->getName()}.timeout", 30);

        Log::debug("[ai:{$this->getName()}] Sending request.", [
            'model'  => $model,
            'chars'  => mb_strlen($request->prompt),
        ]);

        $start = microtime(true);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($timeout)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw AiProviderUnavailableException::connectionFailed($this->getName(), $url, $e);
        }

        $ms = round((microtime(true) - $start) * 1000, 2);

        if ($response->status() === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 0) ?: null;
            throw AiRateLimitException::quotaExceeded($this->getName(), $retryAfter);
        }

        if ($response->failed()) {
            throw AiProviderUnavailableException::httpError(
                $this->getName(),
                $response->status(),
                mb_substr($response->body(), 0, 500),
            );
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw AiInvalidResponseException::unparseable($this->getName(), $response->body());
        }

        $aiResponse = $this->parseResponse($body, $model, $ms);

        Log::debug("[ai:{$this->getName()}] Response received.", [
            'model'         => $model,
            'latency_ms'    => $ms,
            'input_tokens'  => $aiResponse->inputTokens,
            'output_tokens' => $aiResponse->outputTokens,
        ]);

        return $aiResponse;
    }

    public function getDefaultModel(): string
    {
        return config("ai.providers.{$this->getName()}.default_model", '');
    }

    public function getSupportedModels(): array
    {
        return config("ai.providers.{$this->getName()}.models", []);
    }

    // ─── Template methods ──────────────────────────────────────────────────

    /**
     * Construiește payload-ul HTTP specific providerului.
     */
    abstract protected function buildPayload(AiRequest $request, string $model): array;

    /**
     * Parsează body-ul JSON al răspunsului și returnează AiResponse.
     */
    abstract protected function parseResponse(array $body, string $model, float $latencyMs): AiResponse;

    /**
     * URL-ul endpoint-ului pentru modelul dat.
     */
    abstract protected function getApiUrl(string $model): string;

    /**
     * Header-ele HTTP necesare (Authorization, api-key etc.).
     */
    abstract protected function getHeaders(): array;

    // ─── Helpers ──────────────────────────────────────────────────────────

    protected function requireField(array $data, string $field): mixed
    {
        if (! array_key_exists($field, $data)) {
            throw AiInvalidResponseException::missingField($this->getName(), $field);
        }

        return $data[$field];
    }

    protected function requireNestedField(array $data, string $path): mixed
    {
        $keys    = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (! is_array($current) || ! array_key_exists($key, $current)) {
                throw AiInvalidResponseException::missingField($this->getName(), $path);
            }
            $current = $current[$key];
        }

        return $current;
    }
}
