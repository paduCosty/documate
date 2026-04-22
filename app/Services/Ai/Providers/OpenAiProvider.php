<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;

/**
 * Provider pentru OpenAI (GPT-4o, GPT-3.5-turbo etc.).
 *
 * API: POST /v1/chat/completions
 * Docs: https://platform.openai.com/docs/api-reference/chat
 */
class OpenAiProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return (bool) config('ai.providers.openai.enabled', false)
            && ! empty(config('ai.providers.openai.api_key'));
    }

    protected function getApiUrl(string $model): string
    {
        $baseUrl = rtrim(config('ai.providers.openai.base_url'), '/');

        return "{$baseUrl}/chat/completions";
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . config('ai.providers.openai.api_key'),
        ];
    }

    protected function buildPayload(AiRequest $request, string $model): array
    {
        $payload = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'user', 'content' => $request->prompt],
            ],
            'temperature' => $request->temperature,
        ];

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if (! empty($request->options)) {
            $payload = array_merge($payload, $request->options);
        }

        return $payload;
    }

    protected function parseResponse(array $body, string $model, float $latencyMs): AiResponse
    {
        $text = $this->requireNestedField($body, 'choices.0.message.content');

        if (trim((string) $text) === '') {
            throw AiInvalidResponseException::emptyResponse($this->getName());
        }

        $usage = $body['usage'] ?? [];

        return new AiResponse(
            text:         trim((string) $text),
            providerUsed: $this->getName(),
            modelUsed:    $body['model'] ?? $model,
            inputTokens:  (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
            latencyMs:    $latencyMs,
        );
    }
}
