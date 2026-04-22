<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;

/**
 * Provider pentru Anthropic Claude (Haiku, Sonnet, Opus).
 *
 * API: POST /v1/messages
 * Docs: https://docs.anthropic.com/en/api/messages
 */
class ClaudeProvider extends AbstractAiProvider
{
    private const ANTHROPIC_VERSION = '2023-06-01';
    private const DEFAULT_MAX_TOKENS = 4096;

    public function getName(): string
    {
        return 'claude';
    }

    public function isAvailable(): bool
    {
        return (bool) config('ai.providers.claude.enabled', false)
            && ! empty(config('ai.providers.claude.api_key'));
    }

    protected function getApiUrl(string $model): string
    {
        $baseUrl = rtrim(config('ai.providers.claude.base_url'), '/');

        return "{$baseUrl}/messages";
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type'      => 'application/json',
            'x-api-key'         => config('ai.providers.claude.api_key'),
            'anthropic-version' => self::ANTHROPIC_VERSION,
        ];
    }

    protected function buildPayload(AiRequest $request, string $model): array
    {
        $payload = [
            'model'      => $model,
            'max_tokens' => $request->maxTokens ?? self::DEFAULT_MAX_TOKENS,
            'messages'   => [
                ['role' => 'user', 'content' => $request->prompt],
            ],
            'temperature' => $request->temperature,
        ];

        if (! empty($request->options)) {
            $payload = array_merge($payload, $request->options);
        }

        return $payload;
    }

    protected function parseResponse(array $body, string $model, float $latencyMs): AiResponse
    {
        $text = $this->requireNestedField($body, 'content.0.text');

        if (trim((string) $text) === '') {
            throw AiInvalidResponseException::emptyResponse($this->getName());
        }

        $usage = $body['usage'] ?? [];

        return new AiResponse(
            text:         trim((string) $text),
            providerUsed: $this->getName(),
            modelUsed:    $body['model'] ?? $model,
            inputTokens:  (int) ($usage['input_tokens'] ?? 0),
            outputTokens: (int) ($usage['output_tokens'] ?? 0),
            latencyMs:    $latencyMs,
        );
    }
}
