<?php

namespace App\Services\Ai\ValueObjects;

/**
 * Răspuns imutabil de la un AI provider.
 *
 * `usedFallback` → true dacă providerul principal a eșuat și s-a folosit fallback-ul.
 */
final class AiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly string $providerUsed,
        public readonly string $modelUsed,
        public readonly int    $inputTokens,
        public readonly int    $outputTokens,
        public readonly float  $latencyMs,
        public readonly bool   $usedFallback = false,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    public function isEmpty(): bool
    {
        return trim($this->text) === '';
    }

    public function toArray(): array
    {
        return [
            'provider_used'  => $this->providerUsed,
            'model_used'     => $this->modelUsed,
            'input_tokens'   => $this->inputTokens,
            'output_tokens'  => $this->outputTokens,
            'total_tokens'   => $this->totalTokens(),
            'latency_ms'     => $this->latencyMs,
            'used_fallback'  => $this->usedFallback,
        ];
    }
}
