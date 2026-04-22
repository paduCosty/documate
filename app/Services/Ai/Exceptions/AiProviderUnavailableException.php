<?php

namespace App\Services\Ai\Exceptions;

class AiProviderUnavailableException extends AiProviderException
{
    public static function connectionFailed(string $provider, string $url, \Throwable $cause): static
    {
        return new static(
            "AI provider \"{$provider}\" is unreachable at {$url}: {$cause->getMessage()}",
            $provider,
            0,
            $cause,
        );
    }

    public static function timeout(string $provider, int $seconds): static
    {
        return new static(
            "AI provider \"{$provider}\" timed out after {$seconds}s.",
            $provider,
        );
    }

    public static function httpError(string $provider, int $statusCode, string $body): static
    {
        return new static(
            "AI provider \"{$provider}\" returned HTTP {$statusCode}: {$body}",
            $provider,
            $statusCode,
        );
    }
}
