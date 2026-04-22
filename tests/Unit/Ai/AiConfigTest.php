<?php

namespace Tests\Unit\Ai;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Testează că config/ai.php se încarcă corect și conține toate valorile necesare.
 * Rulează cu: php artisan test --filter=AiConfigTest
 */
class AiConfigTest extends TestCase
{
    // ─── Structură config ────────────────────────────────────────────────────

    public function test_config_has_required_top_level_keys(): void
    {
        $this->assertNotNull(config("ai.default"));
        $this->assertNotNull(config("ai.fallback"));
        $this->assertIsArray(config("ai.providers"));
        $this->assertIsArray(config("ai.extraction"));
        $this->assertIsArray(config("ai.limits"));
    }

    public function test_default_provider_is_ollama(): void
    {
        $this->assertEquals("ollama", config("ai.default"));
    }

    public function test_fallback_provider_is_gemini(): void
    {
        $this->assertEquals("gemini", config("ai.fallback"));
    }

    // ─── Providers ───────────────────────────────────────────────────────────

    public function test_all_four_providers_exist(): void
    {
        $providers = config("ai.providers");

        $this->assertArrayHasKey("ollama", $providers);
        $this->assertArrayHasKey("gemini", $providers);
        $this->assertArrayHasKey("openai", $providers);
        $this->assertArrayHasKey("claude", $providers);
    }

    #[DataProvider("providerNamesProvider")]
    public function test_each_provider_has_required_keys(string $provider): void
    {
        $config = config("ai.providers.{$provider}");

        $this->assertArrayHasKey("enabled", $config, "{$provider} missing enabled");
        $this->assertArrayHasKey("default_model", $config, "{$provider} missing default_model");
        $this->assertArrayHasKey("models", $config, "{$provider} missing models");
        $this->assertArrayHasKey("timeout", $config, "{$provider} missing timeout");
        $this->assertIsArray($config["models"], "{$provider}.models should be array");
        $this->assertIsInt($config["timeout"], "{$provider}.timeout should be int");
    }

    public static function providerNamesProvider(): array
    {
        return [
            "ollama" => ["ollama"],
            "gemini" => ["gemini"],
            "openai" => ["openai"],
            "claude" => ["claude"],
        ];
    }

    public function test_ollama_has_base_url(): void
    {
        $this->assertArrayHasKey("base_url", config("ai.providers.ollama"));
        $this->assertStringContainsString("11434", config("ai.providers.ollama.base_url"));
    }

    public function test_cloud_providers_have_api_key_key(): void
    {
        foreach (["gemini", "openai", "claude"] as $provider) {
            $this->assertArrayHasKey("api_key", config("ai.providers.{$provider}"), "{$provider} missing api_key");
        }
    }

    public function test_ollama_default_model_is_mistral(): void
    {
        $this->assertEquals("mistral", config("ai.providers.ollama.default_model"));
    }

    public function test_ollama_is_enabled_by_default(): void
    {
        $this->assertTrue(config("ai.providers.ollama.enabled"));
    }

    public function test_cloud_providers_are_disabled_by_default(): void
    {
        foreach (["gemini", "openai", "claude"] as $provider) {
            $this->assertFalse(
                config("ai.providers.{$provider}.enabled"),
                "{$provider} should be disabled by default"
            );
        }
    }

    // ─── Extraction settings ─────────────────────────────────────────────────

    public function test_extraction_settings_are_valid(): void
    {
        $extraction = config("ai.extraction");

        $this->assertIsInt($extraction["max_file_size_mb"]);
        $this->assertIsInt($extraction["max_pages"]);
        $this->assertIsInt($extraction["json_retries"]);
        $this->assertIsInt($extraction["text_max_chars"]);

        $this->assertGreaterThan(0, $extraction["max_file_size_mb"]);
        $this->assertGreaterThan(0, $extraction["max_pages"]);
        $this->assertGreaterThan(0, $extraction["json_retries"]);
        $this->assertGreaterThan(0, $extraction["text_max_chars"]);
    }

    public function test_json_retries_is_reasonable(): void
    {
        $retries = config("ai.extraction.json_retries");
        $this->assertGreaterThanOrEqual(1, $retries);
        $this->assertLessThanOrEqual(10, $retries, "Too many retries would slow down the app");
    }

    // ─── Limits ──────────────────────────────────────────────────────────────

    public function test_limits_have_required_plan_keys(): void
    {
        $limits = config("ai.limits");

        $this->assertArrayHasKey("free", $limits);
        $this->assertArrayHasKey("pro", $limits);
        $this->assertArrayHasKey("guest", $limits);
    }

    public function test_pro_limit_is_null_meaning_unlimited(): void
    {
        $this->assertNull(config("ai.limits.pro"), "Pro plan should have no limit (null)");
    }

    public function test_free_limit_is_positive_integer(): void
    {
        $limit = config("ai.limits.free");
        $this->assertIsInt($limit);
        $this->assertGreaterThan(0, $limit);
    }

    public function test_guest_limit_is_less_than_or_equal_to_free_limit(): void
    {
        $this->assertLessThanOrEqual(
            config("ai.limits.free"),
            config("ai.limits.guest"),
            "Guest limit should not exceed free user limit"
        );
    }
}
