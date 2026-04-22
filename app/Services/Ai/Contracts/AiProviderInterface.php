<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;

/**
 * Contract pentru orice implementare de AI Provider.
 *
 * Pentru a adăuga un provider nou:
 *   1. Implementează această interfață
 *   2. Înregistrează-l în AiProviderFactory::PROVIDERS
 *   3. Adaugă configurația în config/ai.php → providers
 *   4. Adaugă variabilele de env în .env.example
 */
interface AiProviderInterface
{
    /**
     * Trimite un prompt și returnează răspunsul AI.
     *
     * @throws \App\Services\Ai\Exceptions\AiProviderUnavailableException
     * @throws \App\Services\Ai\Exceptions\AiInvalidResponseException
     * @throws \App\Services\Ai\Exceptions\AiRateLimitException
     */
    public function complete(AiRequest $request): AiResponse;

    /**
     * Verifică dacă providerul e configurat și poate fi folosit
     * (API key prezentă, endpoint accesibil etc.).
     */
    public function isAvailable(): bool;

    /**
     * Slug-ul unic al providerului (ex: "ollama", "gemini").
     * Corespunde cheii din config/ai.php → providers.
     */
    public function getName(): string;

    /**
     * Modelul default al providerului, din config.
     */
    public function getDefaultModel(): string;

    /**
     * Lista completă de modele suportate de acest provider.
     *
     * @return string[]
     */
    public function getSupportedModels(): array;
}
