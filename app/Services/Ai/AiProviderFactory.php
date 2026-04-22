<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Providers\ClaudeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\Providers\OpenAiProvider;

/**
 * Factory for AI providers.
 *
 * To add a new provider:
 *   1. Implement AiProviderInterface (or extend AbstractAiProvider)
 *   2. Add an entry to the PROVIDERS map below
 *   3. Add its configuration block in config/ai.php → providers
 *   4. Add the required env variables to .env.example
 */
class AiProviderFactory
{
    /**
     * Registru central de provideri.
     * Cheile corespund valorilor din config('ai.default') și config('ai.fallback').
     */
    private const PROVIDERS = [
        'ollama' => OllamaProvider::class,
        'gemini' => GeminiProvider::class,
        'openai' => OpenAiProvider::class,
        'claude' => ClaudeProvider::class,
    ];

    /**
     * Returnează providerul implicit din config('ai.default').
     */
    public static function fromConfig(): AiProviderInterface
    {
        return static::make(config('ai.default', 'ollama'));
    }

    /**
     * Returnează providerul de fallback din config('ai.fallback').
     * Returnează null dacă fallback-ul nu e configurat sau e același cu default.
     */
    public static function fallback(): ?AiProviderInterface
    {
        $fallbackSlug = config('ai.fallback');

        if (! $fallbackSlug || $fallbackSlug === config('ai.default')) {
            return null;
        }

        return static::make($fallbackSlug);
    }

    /**
     * Returnează un provider specific după slug.
     *
     * @throws \InvalidArgumentException pentru provideri necunoscuți
     */
    public static function make(string $provider): AiProviderInterface
    {
        if (! array_key_exists($provider, self::PROVIDERS)) {
            throw new \InvalidArgumentException(
                "Unknown AI provider \"{$provider}\". "
                . 'Available: ' . implode(', ', array_keys(self::PROVIDERS))
            );
        }

        return new (self::PROVIDERS[$provider])();
    }

    /**
     * @return string[]
     */
    public static function availableProviders(): array
    {
        return array_keys(self::PROVIDERS);
    }
}
