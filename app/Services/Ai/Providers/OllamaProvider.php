<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;

/**
 * Provider pentru Ollama (modele locale: Mistral, Llama etc.).
 *
 * API: POST /api/generate
 * Docs: https://github.com/ollama/ollama/blob/main/docs/api.md
 */
class OllamaProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'ollama';
    }

    public function isAvailable(): bool
    {
        return (bool) config('ai.providers.ollama.enabled', false);
    }

    protected function getApiUrl(string $model): string
    {
        $baseUrl = rtrim(config('ai.providers.ollama.base_url', 'http://host.docker.internal:11434'), '/');

        return "{$baseUrl}/api/generate";
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function buildPayload(AiRequest $request, string $model): array
    {
        $payload = [
            'model'  => $model,
            'prompt' => $request->prompt,
            'stream' => false,
            'options' => array_merge(
                ['temperature' => $request->temperature],
                $request->options,
            ),
        ];

        if ($request->maxTokens !== null) {
            $payload['options']['num_predict'] = $request->maxTokens;
        }

        return $payload;
    }

    protected function parseResponse(array $body, string $model, float $latencyMs): AiResponse
    {
        $text = $this->requireField($body, 'response');

        if (trim((string) $text) === '') {
            throw AiInvalidResponseException::emptyResponse($this->getName());
        }

        return new AiResponse(
            text:         trim((string) $text),
            providerUsed: $this->getName(),
            modelUsed:    $body['model'] ?? $model,
            inputTokens:  (int) ($body['prompt_eval_count'] ?? 0),
            outputTokens: (int) ($body['eval_count'] ?? 0),
            latencyMs:    $latencyMs,
        );
    }
}
