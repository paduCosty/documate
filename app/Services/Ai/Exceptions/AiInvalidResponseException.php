<?php

namespace App\Services\Ai\Exceptions;

class AiInvalidResponseException extends AiProviderException
{
    public static function emptyResponse(string $provider): static
    {
        return new static(
            "AI provider \"{$provider}\" returned an empty response.",
            $provider,
        );
    }

    public static function unparseable(string $provider, string $raw): static
    {
        $preview = mb_substr($raw, 0, 200);

        return new static(
            "AI provider \"{$provider}\" returned an unparseable response: {$preview}",
            $provider,
        );
    }

    public static function missingField(string $provider, string $field): static
    {
        return new static(
            "AI provider \"{$provider}\" response is missing expected field \"{$field}\".",
            $provider,
        );
    }
}
