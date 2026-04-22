<?php

namespace App\Services\Pdf\Processors;

use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\Exceptions\OcrNotAvailableException;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;
use Illuminate\Support\Facades\Log;

/**
 * Automatically selects the best extraction strategy:
 *   1. Try NativePdfProcessor (pdftotext)
 *   2. If extracted text is below the min_chars_per_page threshold → likely scanned
 *      → fall back to OcrPdfProcessor if available
 *   3. If OCR is unavailable → return the native result with a scanned warning flag
 *
 * This is the recommended driver for production use.
 */
class AutoPdfProcessor extends AbstractPdfProcessor
{
    public function __construct(
        private readonly PdfProcessorInterface $native,
        private readonly PdfProcessorInterface $ocr,
    ) {}

    public function getName(): string
    {
        return 'auto';
    }

    public function isAvailable(): bool
    {
        return $this->native->isAvailable();
    }

    public function getMetadata(string $filePath): PdfMetadata
    {
        return $this->native->getMetadata($filePath);
    }

    public function extract(string $filePath): PdfProcessingResult
    {
        $nativeResult = $this->native->extract($filePath);

        if (! $this->isLikelyScanned($nativeResult)) {
            return $nativeResult;
        }

        Log::info('[auto] PDF appears scanned (low char/page ratio). Attempting OCR.', [
            'file'           => basename($filePath),
            'chars'          => $nativeResult->charCount(),
            'pages'          => $nativeResult->metadata->pageCount,
            'chars_per_page' => $this->charsPerPage($nativeResult),
        ]);

        if (! $this->ocr->isAvailable()) {
            Log::warning('[auto] OCR not available. Returning native result with scanned flag.');

            return $this->markAsLikelyScanned($nativeResult);
        }

        try {
            $ocrResult = $this->ocr->extract($filePath);

            Log::info('[auto] OCR succeeded.', [
                'native_chars' => $nativeResult->charCount(),
                'ocr_chars'    => $ocrResult->charCount(),
            ]);

            return new PdfProcessingResult(
                text:             $ocrResult->text,
                metadata:         $ocrResult->metadata,
                processorUsed:    $this->getName(),
                processingTimeMs: $nativeResult->processingTimeMs + $ocrResult->processingTimeMs,
                fellBackToOcr:    true,
            );
        } catch (\Throwable $e) {
            Log::error('[auto] OCR failed, falling back to native result.', ['error' => $e->getMessage()]);

            return $this->markAsLikelyScanned($nativeResult);
        }
    }

    public function extractPages(string $filePath, int $fromPage, int $toPage): PdfProcessingResult
    {
        $nativeResult = $this->native->extractPages($filePath, $fromPage, $toPage);

        if (! $this->isLikelyScanned($nativeResult)) {
            return $nativeResult;
        }

        if (! $this->ocr->isAvailable()) {
            return $this->markAsLikelyScanned($nativeResult);
        }

        try {
            $ocrResult = $this->ocr->extractPages($filePath, $fromPage, $toPage);

            return new PdfProcessingResult(
                text:             $ocrResult->text,
                metadata:         $ocrResult->metadata,
                processorUsed:    $this->getName(),
                processingTimeMs: $nativeResult->processingTimeMs + $ocrResult->processingTimeMs,
                fellBackToOcr:    true,
            );
        } catch (\Throwable $e) {
            Log::error('[auto] OCR failed on page range.', ['error' => $e->getMessage()]);

            return $this->markAsLikelyScanned($nativeResult);
        }
    }

    // -------------------------------------------------------------------------

    private function isLikelyScanned(PdfProcessingResult $result): bool
    {
        $threshold = config('ai.pdf_processor.min_chars_per_page', 50);

        return $this->charsPerPage($result) < $threshold;
    }

    private function charsPerPage(PdfProcessingResult $result): float
    {
        $pages = max($result->metadata->pageCount, 1);

        return $result->charCount() / $pages;
    }

    private function markAsLikelyScanned(PdfProcessingResult $result): PdfProcessingResult
    {
        $scannedMetadata = new PdfMetadata(
            filePath:        $result->metadata->filePath,
            pageCount:       $result->metadata->pageCount,
            fileSizeBytes:   $result->metadata->fileSizeBytes,
            isEncrypted:     $result->metadata->isEncrypted,
            isLikelyScanned: true,
            title:           $result->metadata->title,
            author:          $result->metadata->author,
            creator:         $result->metadata->creator,
        );

        return new PdfProcessingResult(
            text:             $result->text,
            metadata:         $scannedMetadata,
            processorUsed:    $result->processorUsed,
            processingTimeMs: $result->processingTimeMs,
            fellBackToOcr:    false,
        );
    }
}
