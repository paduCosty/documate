<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                "slug"     => "ollama",
                "name"     => "Ollama (Local)",
                "enabled"  => true,
                "base_url" => env("OLLAMA_BASE_URL", "http://host.docker.internal:11434"),
                "timeout"  => 120,
                "metadata" => json_encode(["type" => "local"]),
            ],
            [
                "slug"     => "gemini",
                "name"     => "Google Gemini",
                "enabled"  => false,
                "base_url" => "https://generativelanguage.googleapis.com/v1beta",
                "timeout"  => 30,
                "metadata" => json_encode(["type" => "cloud", "requires_key" => true]),
            ],
            [
                "slug"     => "openai",
                "name"     => "OpenAI",
                "enabled"  => false,
                "base_url" => "https://api.openai.com/v1",
                "timeout"  => 30,
                "metadata" => json_encode(["type" => "cloud", "requires_key" => true]),
            ],
            [
                "slug"     => "claude",
                "name"     => "Anthropic Claude",
                "enabled"  => false,
                "base_url" => "https://api.anthropic.com/v1",
                "timeout"  => 30,
                "metadata" => json_encode(["type" => "cloud", "requires_key" => true]),
            ],
        ];

        foreach ($providers as $provider) {
            DB::table("ai_providers")->updateOrInsert(
                ["slug" => $provider["slug"]],
                array_merge($provider, [
                    "created_at" => now(),
                    "updated_at" => now(),
                ])
            );
        }

        $models = [
            "ollama" => [
                ["slug" => "mistral",      "name" => "Mistral 7B",       "is_default" => true],
                ["slug" => "llama3.2",     "name" => "Llama 3.2",        "is_default" => false],
                ["slug" => "llama3.2:3b",  "name" => "Llama 3.2 (3B)",   "is_default" => false],
                ["slug" => "llama3.1:8b",  "name" => "Llama 3.1 (8B)",   "is_default" => false],
            ],
            "gemini" => [
                ["slug" => "gemini-2.0-flash", "name" => "Gemini 2.0 Flash", "is_default" => true],
                ["slug" => "gemini-1.5-flash", "name" => "Gemini 1.5 Flash", "is_default" => false],
                ["slug" => "gemini-1.5-pro",   "name" => "Gemini 1.5 Pro",   "is_default" => false],
            ],
            "openai" => [
                ["slug" => "gpt-4o-mini",     "name" => "GPT-4o Mini",    "is_default" => true],
                ["slug" => "gpt-4o",          "name" => "GPT-4o",         "is_default" => false],
                ["slug" => "gpt-3.5-turbo",   "name" => "GPT-3.5 Turbo",  "is_default" => false],
            ],
            "claude" => [
                ["slug" => "claude-haiku-4-5-20251001", "name" => "Claude Haiku 4.5", "is_default" => true],
                ["slug" => "claude-sonnet-4-6",         "name" => "Claude Sonnet 4.6", "is_default" => false],
                ["slug" => "claude-opus-4-7",           "name" => "Claude Opus 4.7",   "is_default" => false],
            ],
        ];

        foreach ($models as $providerSlug => $modelList) {
            $providerId = DB::table("ai_providers")->where("slug", $providerSlug)->value("id");
            if (! $providerId) {
                continue;
            }

            foreach ($modelList as $model) {
                DB::table("ai_models")->updateOrInsert(
                    ["provider_id" => $providerId, "slug" => $model["slug"]],
                    array_merge($model, [
                        "provider_id" => $providerId,
                        "enabled"     => true,
                        "created_at"  => now(),
                        "updated_at"  => now(),
                    ])
                );
            }
        }
    }
}
