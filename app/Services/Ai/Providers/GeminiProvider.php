<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;

/**
 * Provider pentru Google Gemini.
 *
 * API: POST /v1beta/models/{model}:generateContent?key={apiKey}
 * Docs: https://ai.google.dev/api/generate-content
 */
class GeminiProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'gemini';
    }

    public function isAvailable(): bool
    {
        return (bool) config('ai.providers.gemini.enabled', false)
            && ! empty(config('ai.providers.gemini.api_key'));
    }

    protected function getApiUrl(string $model): string
    {
        $baseUrl = rtrim(config('ai.providers.gemini.base_url'), '/');
        $apiKey  = config('ai.providers.gemini.api_key');

        return "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";
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
            'contents' => [
                [
                    'parts' => [
                        ['text' => $request->prompt],
                    ],
                ],
            ],
            'generationConfig' => array_merge(
                ['temperature' => $request->temperature],
                $request->options,
            ),
        ];

        if ($request->maxTokens !== null) {
            $payload['generationConfig']['maxOutputTokens'] = $request->maxTokens;
        }

        return $payload;
    }

    protected function parseResponse(array $body, string $model, float $latencyMs): AiResponse
    {
        $text = $this->requireNestedField($body, 'candidates.0.content.parts.0.text');

        if (trim((string) $text) === '') {
            throw AiInvalidResponseException::emptyResponse($this->getName());
        }

        $usage = $body['usageMetadata'] ?? [];

        return new AiResponse(
            text:         trim((string) $text),
            providerUsed: $this->getName(),
            modelUsed:    $model,
            inputTokens:  (int) ($usage['promptTokenCount'] ?? 0),
            outputTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
            latencyMs:    $latencyMs,
        );
    }
}
