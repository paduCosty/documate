<?php

namespace App\Services\Ai\ValueObjects;

/**
 * Cerere imutabilă către un AI provider.
 *
 * `model` null → providerul folosește propriul default din config.
 * `options` → parametri specifici providerului (ex: top_p, seed).
 */
final class AiRequest
{
    public function __construct(
        public readonly string  $prompt,
        public readonly ?string $model       = null,
        public readonly float   $temperature = 0.1,
        public readonly ?int    $maxTokens   = null,
        public readonly array   $options     = [],
    ) {}

    public function withModel(string $model): static
    {
        return new static(
            prompt:      $this->prompt,
            model:       $model,
            temperature: $this->temperature,
            maxTokens:   $this->maxTokens,
            options:     $this->options,
        );
    }

    public function withOption(string $key, mixed $value): static
    {
        return new static(
            prompt:      $this->prompt,
            model:       $this->model,
            temperature: $this->temperature,
            maxTokens:   $this->maxTokens,
            options:     array_merge($this->options, [$key => $value]),
        );
    }
}
