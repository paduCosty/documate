<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/**
 * Excepție de bază pentru toate erorile din AI Provider Layer.
 *
 * Ierarhie:
 *   AiProviderException
 *   ├── AiProviderUnavailableException  — provider down, timeout, rețea
 *   ├── AiInvalidResponseException      — răspuns neparsabil sau gol
 *   └── AiRateLimitException            — 429 / quota depășit
 */
class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $provider = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
