<?php

namespace App\Services\Ai\Exceptions;

class AiRateLimitException extends AiProviderException
{
    public static function quotaExceeded(string $provider, ?int $retryAfterSeconds = null): static
    {
        $suffix = $retryAfterSeconds ? " Retry after {$retryAfterSeconds}s." : '';

        return new static(
            "AI provider \"{$provider}\" rate limit exceeded.{$suffix}",
            $provider,
        );
    }
}
