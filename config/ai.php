<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Single source of truth pentru toți providerii AI folosiți în Documate.
    |
    | - default  : providerul activ (schimbă cu AI_PROVIDER în .env)
    | - fallback : providerul folosit dacă cel default eșuează
    |
    | Pentru a adăuga un provider nou:
    |   1. Adaugă o intrare în "providers" mai jos
    |   2. Creează App\Services\Ai\Providers\{Name}Provider
    |   3. Înregistrează-l în AiProviderFactory
    |   4. Adaugă variabilele necesare în .env.example
    |
    */

    "default"  => env("AI_PROVIDER", "ollama"),
    "fallback" => env("AI_FALLBACK_PROVIDER", "gemini"),

    "providers" => [

        "ollama" => [
            "enabled"       => (bool) env("OLLAMA_ENABLED", true),
            "base_url"      => env("OLLAMA_BASE_URL", "http://host.docker.internal:11434"),
            "default_model" => env("OLLAMA_MODEL", "mistral"),
            "models"        => ["mistral", "llama3.2", "llama3.2:3b", "llama3.1:8b"],
            "timeout"       => (int) env("OLLAMA_TIMEOUT", 120),
        ],

        "gemini" => [
            "enabled"       => (bool) env("GEMINI_ENABLED", false),
            "api_key"       => env("GEMINI_API_KEY"),
            "base_url"      => "https://generativelanguage.googleapis.com/v1beta",
            "default_model" => env("GEMINI_MODEL", "gemini-2.0-flash"),
            "models"        => ["gemini-2.0-flash", "gemini-1.5-flash", "gemini-1.5-pro"],
            "timeout"       => (int) env("GEMINI_TIMEOUT", 30),
        ],

        "openai" => [
            "enabled"       => (bool) env("OPENAI_ENABLED", false),
            "api_key"       => env("OPENAI_API_KEY"),
            "base_url"      => "https://api.openai.com/v1",
            "default_model" => env("OPENAI_MODEL", "gpt-4o-mini"),
            "models"        => ["gpt-4o-mini", "gpt-4o", "gpt-3.5-turbo"],
            "timeout"       => (int) env("OPENAI_TIMEOUT", 30),
        ],

        "claude" => [
            "enabled"       => (bool) env("CLAUDE_ENABLED", false),
            "api_key"       => env("ANTHROPIC_API_KEY"),
            "base_url"      => "https://api.anthropic.com/v1",
            "default_model" => env("CLAUDE_MODEL", "claude-haiku-4-5-20251001"),
            "models"        => ["claude-haiku-4-5-20251001", "claude-sonnet-4-6", "claude-opus-4-7"],
            "timeout"       => (int) env("CLAUDE_TIMEOUT", 30),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Processor Configuration
    |--------------------------------------------------------------------------
    |
    | Controlează cum se extrage textul din PDF-uri înainte de a fi trimis AI-ului.
    |
    | Drivere disponibile:
    |   - auto   : încearcă native; dacă textul e insuficient (scanat) → OCR
    |   - native : pdftotext (Poppler) — rapid, fără dependențe extra
    |   - ocr    : Tesseract OCR — necesar pentru PDF-uri scanate/imagini
    |
    | Pentru a adăuga un driver nou:
    |   1. Implementează PdfProcessorInterface
    |   2. Înregistrează-l în PdfProcessorFactory::PROCESSORS
    |   3. Adaugă variabilele de env necesare mai jos
    |
    */

    "pdf_processor" => [
        "driver"             => env("PDF_PROCESSOR_DRIVER", "auto"),

        "pdftotext_path"     => env("PDFTOTEXT_PATH", "/usr/bin/pdftotext"),
        "pdfinfo_path"       => env("PDFINFO_PATH", "/usr/bin/pdfinfo"),

        "tesseract_path"     => env("TESSERACT_PATH", "/usr/bin/tesseract"),
        "tesseract_lang"     => env("TESSERACT_LANG", "ron+eng"),
        "ocr_dpi"            => (int) env("OCR_DPI", 300),

        // AutoPdfProcessor: dacă media chars/pagină e sub acest prag, PDF-ul e considerat scanat
        "min_chars_per_page" => (int) env("PDF_MIN_CHARS_PER_PAGE", 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction Engine Configuration
    |--------------------------------------------------------------------------
    */

    "extraction" => [
        "max_file_size_mb" => (int) env("AI_MAX_FILE_SIZE_MB", 20),
        "max_pages"        => (int) env("AI_MAX_PAGES", 50),
        "json_retries"     => (int) env("AI_JSON_RETRIES", 3),
        "text_max_chars"   => (int) env("AI_TEXT_MAX_CHARS", 8000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Limits
    |--------------------------------------------------------------------------
    */

    "limits" => [
        "free"  => (int) env("AI_FREE_DAILY_LIMIT", 3),
        "pro"   => null,
        "guest" => (int) env("AI_GUEST_DAILY_LIMIT", 1),
    ],

];
