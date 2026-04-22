<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiProviderOrchestrator;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiInvalidResponseException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Ai\Providers\ClaudeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;
use Tests\TestCase;

/**
 * Teste unitare pentru AI Provider Layer (Faza 4).
 * Rulează cu: php artisan test --filter=AiProviderTest
 */
class AiProviderTest extends TestCase
{
    // ─── Factory ─────────────────────────────────────────────────────────────

    public function test_factory_returns_ollama_provider(): void
    {
        $provider = AiProviderFactory::make('ollama');
        $this->assertInstanceOf(OllamaProvider::class, $provider);
        $this->assertEquals('ollama', $provider->getName());
    }

    public function test_factory_returns_gemini_provider(): void
    {
        $provider = AiProviderFactory::make('gemini');
        $this->assertInstanceOf(GeminiProvider::class, $provider);
    }

    public function test_factory_returns_openai_provider(): void
    {
        $provider = AiProviderFactory::make('openai');
        $this->assertInstanceOf(OpenAiProvider::class, $provider);
    }

    public function test_factory_returns_claude_provider(): void
    {
        $provider = AiProviderFactory::make('claude');
        $this->assertInstanceOf(ClaudeProvider::class, $provider);
    }

    public function test_factory_throws_for_unknown_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown AI provider/');

        AiProviderFactory::make('nonexistent');
    }

    public function test_factory_lists_all_four_providers(): void
    {
        $providers = AiProviderFactory::availableProviders();

        $this->assertContains('ollama', $providers);
        $this->assertContains('gemini', $providers);
        $this->assertContains('openai', $providers);
        $this->assertContains('claude', $providers);
    }

    public function test_factory_from_config_returns_ollama_by_default(): void
    {
        $provider = AiProviderFactory::fromConfig();
        $this->assertInstanceOf(OllamaProvider::class, $provider);
    }

    public function test_factory_fallback_returns_gemini(): void
    {
        $fallback = AiProviderFactory::fallback();
        $this->assertInstanceOf(GeminiProvider::class, $fallback);
    }

    public function test_factory_fallback_returns_null_when_same_as_default(): void
    {
        config(['ai.default' => 'ollama', 'ai.fallback' => 'ollama']);

        $fallback = AiProviderFactory::fallback();
        $this->assertNull($fallback);
    }

    // ─── IoC Container ───────────────────────────────────────────────────────

    public function test_container_resolves_ai_provider_interface_as_orchestrator(): void
    {
        $provider = app(AiProviderInterface::class);
        $this->assertInstanceOf(AiProviderOrchestrator::class, $provider);
    }

    public function test_orchestrator_wraps_primary_and_fallback(): void
    {
        /** @var AiProviderOrchestrator $orchestrator */
        $orchestrator = app(AiProviderInterface::class);

        $this->assertInstanceOf(OllamaProvider::class, $orchestrator->getPrimary());
        $this->assertInstanceOf(GeminiProvider::class, $orchestrator->getFallback());
    }

    // ─── Provider availability ────────────────────────────────────────────────

    public function test_ollama_is_available_when_enabled(): void
    {
        config(['ai.providers.ollama.enabled' => true]);
        $this->assertTrue(AiProviderFactory::make('ollama')->isAvailable());
    }

    public function test_ollama_is_not_available_when_disabled(): void
    {
        config(['ai.providers.ollama.enabled' => false]);
        $this->assertFalse(AiProviderFactory::make('ollama')->isAvailable());
    }

    public function test_gemini_is_not_available_without_api_key(): void
    {
        config(['ai.providers.gemini.enabled' => true, 'ai.providers.gemini.api_key' => null]);
        $this->assertFalse(AiProviderFactory::make('gemini')->isAvailable());
    }

    public function test_gemini_is_available_with_api_key_and_enabled(): void
    {
        config(['ai.providers.gemini.enabled' => true, 'ai.providers.gemini.api_key' => 'test-key']);
        $this->assertTrue(AiProviderFactory::make('gemini')->isAvailable());
    }

    public function test_openai_is_not_available_without_api_key(): void
    {
        config(['ai.providers.openai.enabled' => true, 'ai.providers.openai.api_key' => '']);
        $this->assertFalse(AiProviderFactory::make('openai')->isAvailable());
    }

    public function test_claude_is_not_available_without_api_key(): void
    {
        config(['ai.providers.claude.enabled' => true, 'ai.providers.claude.api_key' => null]);
        $this->assertFalse(AiProviderFactory::make('claude')->isAvailable());
    }

    // ─── Provider config ─────────────────────────────────────────────────────

    public function test_ollama_default_model_is_mistral(): void
    {
        $this->assertEquals('mistral', AiProviderFactory::make('ollama')->getDefaultModel());
    }

    public function test_ollama_supported_models_contains_mistral(): void
    {
        $models = AiProviderFactory::make('ollama')->getSupportedModels();
        $this->assertContains('mistral', $models);
    }

    public function test_gemini_default_model_is_gemini_flash(): void
    {
        $this->assertStringContainsString('gemini', AiProviderFactory::make('gemini')->getDefaultModel());
    }

    // ─── AiRequest Value Object ───────────────────────────────────────────────

    public function test_ai_request_defaults(): void
    {
        $request = new AiRequest('Hello');

        $this->assertEquals('Hello', $request->prompt);
        $this->assertNull($request->model);
        $this->assertEquals(0.1, $request->temperature);
        $this->assertNull($request->maxTokens);
        $this->assertEmpty($request->options);
    }

    public function test_ai_request_with_model_returns_new_instance(): void
    {
        $original = new AiRequest('Hello');
        $modified = $original->withModel('gpt-4o');

        $this->assertNotSame($original, $modified);
        $this->assertEquals('gpt-4o', $modified->model);
        $this->assertEquals('Hello', $modified->prompt);
    }

    public function test_ai_request_with_option_returns_new_instance(): void
    {
        $original = new AiRequest('Hello');
        $modified = $original->withOption('top_p', 0.9);

        $this->assertNotSame($original, $modified);
        $this->assertEquals(0.9, $modified->options['top_p']);
        $this->assertEmpty($original->options);
    }

    public function test_ai_request_with_option_merges_existing_options(): void
    {
        $request = (new AiRequest('Hello'))
            ->withOption('top_p', 0.9)
            ->withOption('seed', 42);

        $this->assertEquals(0.9, $request->options['top_p']);
        $this->assertEquals(42, $request->options['seed']);
    }

    // ─── AiResponse Value Object ──────────────────────────────────────────────

    public function test_ai_response_total_tokens(): void
    {
        $response = $this->makeResponse(inputTokens: 100, outputTokens: 50);

        $this->assertEquals(150, $response->totalTokens());
    }

    public function test_ai_response_is_empty_for_blank_text(): void
    {
        $response = $this->makeResponse(text: '   ');
        $this->assertTrue($response->isEmpty());
    }

    public function test_ai_response_is_not_empty_for_real_text(): void
    {
        $response = $this->makeResponse(text: '{"key": "value"}');
        $this->assertFalse($response->isEmpty());
    }

    public function test_ai_response_to_array_contains_required_keys(): void
    {
        $response = $this->makeResponse();
        $array    = $response->toArray();

        $this->assertArrayHasKey('provider_used', $array);
        $this->assertArrayHasKey('model_used', $array);
        $this->assertArrayHasKey('input_tokens', $array);
        $this->assertArrayHasKey('output_tokens', $array);
        $this->assertArrayHasKey('total_tokens', $array);
        $this->assertArrayHasKey('latency_ms', $array);
        $this->assertArrayHasKey('used_fallback', $array);
    }

    public function test_ai_response_used_fallback_defaults_to_false(): void
    {
        $response = $this->makeResponse();
        $this->assertFalse($response->usedFallback);
    }

    // ─── Exceptions ──────────────────────────────────────────────────────────

    public function test_provider_unavailable_connection_failed(): void
    {
        $cause = new \RuntimeException('Connection refused');
        $e     = AiProviderUnavailableException::connectionFailed('ollama', 'http://localhost:11434', $cause);

        $this->assertStringContainsString('ollama', $e->getMessage());
        $this->assertStringContainsString('Connection refused', $e->getMessage());
        $this->assertEquals('ollama', $e->getProvider());
        $this->assertSame($cause, $e->getPrevious());
    }

    public function test_provider_unavailable_http_error_status_code(): void
    {
        $e = AiProviderUnavailableException::httpError('gemini', 503, 'Service Unavailable');

        $this->assertEquals(503, $e->getCode());
        $this->assertStringContainsString('503', $e->getMessage());
    }

    public function test_rate_limit_exception_message(): void
    {
        $e = AiRateLimitException::quotaExceeded('openai', 60);

        $this->assertStringContainsString('rate limit', $e->getMessage());
        $this->assertStringContainsString('60', $e->getMessage());
        $this->assertEquals('openai', $e->getProvider());
    }

    public function test_invalid_response_empty(): void
    {
        $e = AiInvalidResponseException::emptyResponse('claude');

        $this->assertStringContainsString('empty', $e->getMessage());
        $this->assertEquals('claude', $e->getProvider());
    }

    public function test_invalid_response_missing_field(): void
    {
        $e = AiInvalidResponseException::missingField('ollama', 'response');

        $this->assertStringContainsString('response', $e->getMessage());
    }

    // ─── Orchestrator ────────────────────────────────────────────────────────

    public function test_orchestrator_returns_primary_response_on_success(): void
    {
        $expected = $this->makeResponse(text: 'Primary result');
        $primary  = $this->mockProvider($expected);

        $orchestrator = new AiProviderOrchestrator($primary, null, maxAttempts: 3);
        $result       = $orchestrator->complete(new AiRequest('Test'));

        $this->assertEquals('Primary result', $result->text);
        $this->assertFalse($result->usedFallback);
    }

    public function test_orchestrator_uses_fallback_when_primary_fails(): void
    {
        $fallbackResponse = $this->makeResponse(text: 'Fallback result', provider: 'gemini');

        $primary  = $this->mockFailingProvider('ollama');
        $fallback = $this->mockProvider($fallbackResponse);

        $orchestrator = new AiProviderOrchestrator($primary, $fallback, maxAttempts: 1, baseDelayMs: 0);
        $result       = $orchestrator->complete(new AiRequest('Test'));

        $this->assertEquals('Fallback result', $result->text);
        $this->assertTrue($result->usedFallback);
    }

    public function test_orchestrator_throws_when_primary_fails_and_no_fallback(): void
    {
        $this->expectException(AiProviderException::class);

        $primary      = $this->mockFailingProvider('ollama');
        $orchestrator = new AiProviderOrchestrator($primary, null, maxAttempts: 1, baseDelayMs: 0);

        $orchestrator->complete(new AiRequest('Test'));
    }

    public function test_orchestrator_throws_primary_exception_when_both_fail(): void
    {
        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('Primary error');

        $primary      = $this->mockFailingProvider('ollama', 'Primary error');
        $fallback     = $this->mockFailingProvider('gemini', 'Fallback error');
        $orchestrator = new AiProviderOrchestrator($primary, $fallback, maxAttempts: 1, baseDelayMs: 0);

        $orchestrator->complete(new AiRequest('Test'));
    }

    public function test_orchestrator_skips_retry_on_rate_limit(): void
    {
        $primary = $this->createMock(AiProviderInterface::class);
        $primary->method('getName')->willReturn('ollama');
        $primary->expects($this->once()) // Exact o singură încercare
            ->method('complete')
            ->willThrowException(AiRateLimitException::quotaExceeded('ollama'));

        $fallbackResponse = $this->makeResponse(text: 'Fallback after rate limit');
        $fallback         = $this->mockProvider($fallbackResponse);

        $orchestrator = new AiProviderOrchestrator($primary, $fallback, maxAttempts: 3, baseDelayMs: 0);
        $result       = $orchestrator->complete(new AiRequest('Test'));

        $this->assertTrue($result->usedFallback);
    }

    public function test_orchestrator_exposes_primary_and_fallback(): void
    {
        $primary  = AiProviderFactory::make('ollama');
        $fallback = AiProviderFactory::make('gemini');

        $orchestrator = new AiProviderOrchestrator($primary, $fallback);

        $this->assertSame($primary, $orchestrator->getPrimary());
        $this->assertSame($fallback, $orchestrator->getFallback());
    }

    public function test_orchestrator_is_available_if_primary_is_available(): void
    {
        config(['ai.providers.ollama.enabled' => true]);

        $primary      = AiProviderFactory::make('ollama');
        $orchestrator = new AiProviderOrchestrator($primary);

        $this->assertTrue($orchestrator->isAvailable());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeResponse(
        string $text         = '{"result": "ok"}',
        string $provider     = 'ollama',
        string $model        = 'mistral',
        int    $inputTokens  = 100,
        int    $outputTokens = 50,
        float  $latencyMs    = 200.0,
    ): AiResponse {
        return new AiResponse(
            text:         $text,
            providerUsed: $provider,
            modelUsed:    $model,
            inputTokens:  $inputTokens,
            outputTokens: $outputTokens,
            latencyMs:    $latencyMs,
        );
    }

    private function mockProvider(AiResponse $response): AiProviderInterface
    {
        $mock = $this->createMock(AiProviderInterface::class);
        $mock->method('getName')->willReturn($response->providerUsed);
        $mock->method('complete')->willReturn($response);
        $mock->method('isAvailable')->willReturn(true);

        return $mock;
    }

    private function mockFailingProvider(string $name, string $message = 'Provider error'): AiProviderInterface
    {
        $mock = $this->createMock(AiProviderInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('isAvailable')->willReturn(true);
        $mock->method('complete')
            ->willThrowException(new AiProviderUnavailableException($message, $name));

        return $mock;
    }
}
