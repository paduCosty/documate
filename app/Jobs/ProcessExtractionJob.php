<?php

namespace App\Jobs;

use App\Models\ExtractionJob;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Extraction\ExtractionService;
use App\Services\Extraction\Exceptions\ExtractionException;
use App\Services\Extraction\Exceptions\JsonExtractionFailedException;
use App\Services\Extraction\Exceptions\PdfNotUsableException;
use App\Services\Output\OutputFormatterFactory;
use App\Services\Pdf\Exceptions\PdfProcessingException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Queue job that runs the full PDF extraction pipeline for one ExtractionJob record.
 *
 * Pipeline (delegates to services built in Phases 3–6):
 *   1. Mark job as "processing"
 *   2. ExtractionService::extract()   — PDF → text → AI → JSON (Phases 3–5)
 *   3. OutputFormatterFactory::make() — JSON → file on disk            (Phase 6)
 *   4. Mark job as "completed", persist metadata
 *
 * Retry strategy:
 *   $tries = 1  — AI-level retry is handled by AiProviderOrchestrator (Phase 4)
 *                 and JSON-level retry by ExtractionService (Phase 5).
 *                 Re-queuing the whole job would re-charge the user for tokens.
 *
 * Error messages are translated to user-friendly strings before being stored
 * in extraction_jobs.error_message so the status page can display them clearly.
 */
class ProcessExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly ExtractionJob $extractionJob,
        private readonly string        $tempFilePath,
        private readonly string        $templateSlug,
        private readonly string        $outputFormat,
        private readonly ?string       $providerOverride = null,
    ) {}

    public function handle(ExtractionService $extractionService): void
    {
        $jobId = $this->extractionJob->uuid;

        Log::channel('extraction')->info("[ProcessExtractionJob] Starting.", [
            'uuid'     => $jobId,
            'template' => $this->templateSlug,
            'format'   => $this->outputFormat,
            'provider' => $this->providerOverride ?? 'default',
        ]);

        try {
            $this->extractionJob->markProcessing();

            $result = $extractionService->extract(
                filePath:         $this->tempFilePath,
                templateSlug:     $this->templateSlug,
                userId:           $this->extractionJob->user_id,
                providerOverride: $this->providerOverride,
            );

            $formatted = $this->formatAndStore($result, $jobId);

            $this->extractionJob->markCompleted(
                outputPath:       $formatted->absolutePath,
                extractedData:    $result->data,
                tokensUsed:       $result->aiMetadata['total_tokens'] ?? 0,
                processingTimeMs: (int) $result->totalMs(),
                pageCount:        $result->pdfMetadata->pageCount,
            );

            Log::channel('extraction')->info("[ProcessExtractionJob] Completed.", [
                'uuid'        => $jobId,
                'output_file' => $formatted->filename,
                'tokens'      => $result->aiMetadata['total_tokens'] ?? 0,
                'total_ms'    => $result->totalMs(),
            ]);

        } catch (\Throwable $e) {
            $friendlyMessage = $this->toFriendlyMessage($e);

            $this->extractionJob->markFailed($friendlyMessage);

            Log::channel('extraction')->error("[ProcessExtractionJob] Failed.", [
                'uuid'      => $jobId,
                'error'     => $e->getMessage(),
                'class'     => get_class($e),
                'displayed' => $friendlyMessage,
            ]);

            throw $e;

        } finally {
            $this->cleanupTempFile();
        }
    }

    public function failed(\Throwable $exception): void
    {
        if (! $this->extractionJob->isFailed()) {
            $this->extractionJob->markFailed($this->toFriendlyMessage($exception));
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function formatAndStore(
        \App\Services\Extraction\ValueObjects\ExtractionResult $result,
        string $uuid,
    ): \App\Services\Output\ValueObjects\FormattedOutput {
        $outputDir = Storage::disk('local')->path(
            $this->extractionJob->storagePath()
        );

        return OutputFormatterFactory::make($this->outputFormat)->format($result, $outputDir);
    }

    /**
     * Translates technical exceptions into short, user-facing error messages.
     * The full technical detail is in the logs.
     */
    private function toFriendlyMessage(\Throwable $e): string
    {
        return match (true) {
            $e instanceof PdfNotUsableException        => $e->getMessage(),
            $e instanceof JsonExtractionFailedException => 'The AI could not extract structured data from this PDF. '
                . 'Try a different template or a cleaner PDF.',
            $e instanceof AiRateLimitException         => 'The AI provider is currently rate-limited. Please try again in a few minutes.',
            $e instanceof AiProviderException          => 'The AI provider is temporarily unavailable. '
                . ($this->providerOverride
                    ? "Provider \"{$this->providerOverride}\" did not respond."
                    : 'Please try again shortly.'),
            $e instanceof PdfProcessingException       => 'The PDF could not be read. '
                . 'It may be corrupt, password-protected, or in an unsupported format.',
            $e instanceof ExtractionException          => $e->getMessage(),
            default                                     => 'An unexpected error occurred. '
                . 'Our team has been notified. Please try again.',
        };
    }

    private function cleanupTempFile(): void
    {
        if ($this->tempFilePath && file_exists($this->tempFilePath)) {
            @unlink($this->tempFilePath);

            $dir = dirname($this->tempFilePath);
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                @rmdir($dir);
            }
        }
    }
}
