<?php

namespace App\Services\Extraction;

use App\Models\ExtractionTemplate;
use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Extraction\Exceptions\JsonExtractionFailedException;
use App\Services\Extraction\Exceptions\PdfNotUsableException;
use App\Services\Extraction\Exceptions\TemplateNotFoundException;
use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full PDF → text → AI → JSON extraction pipeline.
 *
 * Pipeline steps:
 *   1. Load ExtractionTemplate from DB (throws if not found / inactive)
 *   2. Extract text from PDF via PdfProcessorInterface
 *   3. Build prompt from template + PDF text (PromptBuilder)
 *   4. Call AI provider (AiProviderInterface — includes retry + fallback)
 *   5. Parse JSON from AI response (JsonExtractor)
 *   6. Validate against schema (JsonValidator)
 *   7. Normalize data types (ResultNormalizer)
 *   8. Return ExtractionResult
 *
 * JSON retry logic:
 *   If step 5 or 6 fails (invalid JSON / unparseable), a corrective prompt
 *   is built and the AI is called again, up to `config('ai.extraction.json_retries')` times.
 *   This is separate from the AI-level retry in AiProviderOrchestrator (network/timeout).
 */
class ExtractionService
{
    public function __construct(
        private readonly PdfProcessorInterface $pdfProcessor,
        private readonly AiProviderInterface   $aiProvider,
        private readonly PromptBuilder         $promptBuilder,
        private readonly JsonValidator         $jsonValidator,
        private readonly ResultNormalizer      $normalizer,
    ) {}

    /**
     * Runs the full extraction pipeline.
     *
     * @param  string      $filePath        Absolute path to the PDF file.
     * @param  string      $templateSlug    Slug of the ExtractionTemplate to use.
     * @param  int|null    $userId          Used to scope template visibility.
     * @param  string|null $providerOverride Force a specific AI provider slug (optional).
     *
     * @throws TemplateNotFoundException
     * @throws JsonExtractionFailedException
     * @throws \App\Services\Pdf\Exceptions\PdfProcessingException
     * @throws \App\Services\Ai\Exceptions\AiProviderException
     */
    public function extract(
        string  $filePath,
        string  $templateSlug,
        ?int    $userId           = null,
        ?string $providerOverride = null,
    ): ExtractionResult {
        $template = $this->loadTemplate($templateSlug, $userId);
        $provider = $this->resolveProvider($providerOverride);

        Log::channel('extraction')->info('[extraction] Starting pipeline.', [
            'template'  => $templateSlug,
            'provider'  => $provider->getName(),
            'file'      => basename($filePath),
        ]);

        // Step 1 — PDF text extraction.
        // PdfProcessingException (corrupt/unreadable file) is intentionally not caught here —
        // it propagates to the job, which stores it as error_message via markFailed().
        $pdfResult = $this->pdfProcessor->extract($filePath);

        // Guard: fail fast before spending AI tokens on unusable input.
        $this->assertPdfUsable($pdfResult, basename($filePath));

        Log::channel('extraction')->info('[extraction] PDF extracted.', [
            'chars'          => $pdfResult->charCount(),
            'pages'          => $pdfResult->metadata->pageCount,
            'processor'      => $pdfResult->processorUsed,
            'is_scanned'     => $pdfResult->metadata->isLikelyScanned,
            'processing_ms'  => $pdfResult->processingTimeMs,
        ]);

        // Steps 2–5 — AI call + JSON extraction (with JSON-level retries)
        [$data, $warnings, $aiResponse] = $this->runWithJsonRetry(
            $template,
            $pdfResult,
            $provider,
        );

        // Step 6 — Normalize
        $normalized = $this->normalizer->normalize($data);

        Log::channel('extraction')->info('[extraction] Pipeline complete.', [
            'template'          => $templateSlug,
            'provider'          => $aiResponse->providerUsed,
            'model'             => $aiResponse->modelUsed,
            'tokens'            => $aiResponse->totalTokens(),
            'used_fallback'     => $aiResponse->usedFallback,
            'warnings'          => count($warnings),
            'total_ms'          => $pdfResult->processingTimeMs + $aiResponse->latencyMs,
        ]);

        return new ExtractionResult(
            data:              $normalized,
            templateSlug:      $templateSlug,
            pdfMetadata:       $pdfResult->metadata,
            aiMetadata:        $aiResponse->toArray(),
            pdfProcessingMs:   $pdfResult->processingTimeMs,
            aiProcessingMs:    $aiResponse->latencyMs,
            validationWarnings: $warnings,
        );
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * Calls the AI and attempts to parse the JSON response.
     * On JSON failure, builds a corrective prompt and retries.
     *
     * @return array{0: array, 1: string[], 2: \App\Services\Ai\ValueObjects\AiResponse}
     * @throws JsonExtractionFailedException
     */
    private function runWithJsonRetry(
        ExtractionTemplate $template,
        $pdfResult,
        AiProviderInterface $provider,
    ): array {
        $maxAttempts = (int) config('ai.extraction.json_retries', 3);
        $lastRaw     = '';
        $lastError   = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $request = $attempt === 1
                ? $this->promptBuilder->build($template, $pdfResult)
                : $this->promptBuilder->buildCorrectionPrompt(
                    $template,
                    $pdfResult,
                    $lastRaw,
                    $attempt - 1,
                );

            $aiResponse = $provider->complete($request);
            $lastRaw    = $aiResponse->text;

            try {
                ['data' => $data, 'warnings' => $warnings] = $this->jsonValidator->validate(
                    $aiResponse->text,
                    $template->output_schema,
                );

                if ($attempt > 1) {
                    Log::info("[extraction] JSON parsed successfully on attempt {$attempt}.");
                }

                return [$data, $warnings, $aiResponse];

            } catch (\JsonException $e) {
                $lastError = $e->getMessage();

                Log::warning("[extraction] JSON parse failed on attempt {$attempt}/{$maxAttempts}.", [
                    'error'   => $lastError,
                    'preview' => mb_substr($lastRaw, 0, 200),
                ]);
            }
        }

        throw JsonExtractionFailedException::afterRetries($maxAttempts, $lastRaw);
    }

    /**
     * Validates the PDF result before calling the AI.
     * Throws PdfNotUsableException early to avoid wasting tokens.
     *
     * @throws PdfNotUsableException
     */
    private function assertPdfUsable(PdfProcessingResult $result, string $filename): void
    {
        if ($result->metadata->isEncrypted) {
            throw PdfNotUsableException::encrypted($filename);
        }

        $maxPages = (int) config('ai.extraction.max_pages', 50);
        if ($result->metadata->pageCount > $maxPages) {
            throw PdfNotUsableException::tooManyPages($result->metadata->pageCount, $maxPages);
        }

        // A completely empty result means either: scanned PDF with no OCR available,
        // or a corrupt/empty file. Either way, the AI cannot work with it.
        if ($result->isEmpty()) {
            throw PdfNotUsableException::empty($filename);
        }
    }

    private function loadTemplate(string $slug, ?int $userId): ExtractionTemplate
    {
        $template = ExtractionTemplate::findBySlug($slug, $userId);

        if ($template === null) {
            throw TemplateNotFoundException::forSlug($slug);
        }

        return $template;
    }

    /**
     * Returns the provider to use: either the configured override
     * or the application default from the IoC container.
     */
    private function resolveProvider(?string $override): AiProviderInterface
    {
        if ($override !== null) {
            return AiProviderFactory::make($override);
        }

        return $this->aiProvider;
    }
}
