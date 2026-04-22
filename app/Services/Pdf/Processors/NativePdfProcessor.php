<?php

namespace App\Services\Pdf\Processors;

use App\Services\Pdf\Exceptions\PdfNotReadableException;
use App\Services\Pdf\Exceptions\PdfProcessingException;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;

/**
 * Extracts text from native (non-scanned) PDFs using pdftotext (Poppler).
 *
 * Fastest and most reliable processor for PDFs with selectable text.
 * Does not work on scanned PDFs (image-only) — use OcrPdfProcessor for those.
 */
class NativePdfProcessor extends AbstractPdfProcessor
{
    public function getName(): string
    {
        return 'native';
    }

    public function isAvailable(): bool
    {
        $path = config('ai.pdf_processor.pdftotext_path', '/usr/bin/pdftotext');

        return file_exists($path) && is_executable($path);
    }

    public function getMetadata(string $filePath): PdfMetadata
    {
        return $this->parseMetadataFromPdfInfo($filePath);
    }

    public function extract(string $filePath): PdfProcessingResult
    {
        $this->assertFileReadable($filePath);

        $metadata = $this->getMetadata($filePath);

        if ($metadata->isEncrypted) {
            throw PdfNotReadableException::encrypted($filePath);
        }

        [$text, $ms] = $this->timed(fn () => $this->runPdfToText($filePath));

        return new PdfProcessingResult(
            text:             $text,
            metadata:         $metadata,
            processorUsed:    $this->getName(),
            processingTimeMs: $ms,
        );
    }

    public function extractPages(string $filePath, int $fromPage, int $toPage): PdfProcessingResult
    {
        $this->assertFileReadable($filePath);

        $metadata = $this->getMetadata($filePath);

        if ($metadata->isEncrypted) {
            throw PdfNotReadableException::encrypted($filePath);
        }

        [$text, $ms] = $this->timed(fn () => $this->runPdfToText($filePath, $fromPage, $toPage));

        return new PdfProcessingResult(
            text:             $text,
            metadata:         $metadata,
            processorUsed:    $this->getName(),
            processingTimeMs: $ms,
        );
    }

    // -------------------------------------------------------------------------

    private function runPdfToText(string $filePath, ?int $fromPage = null, ?int $toPage = null): string
    {
        $binary = config('ai.pdf_processor.pdftotext_path', '/usr/bin/pdftotext');

        $args = '-layout -enc UTF-8';

        if ($fromPage !== null) {
            $args .= ' -f ' . (int) $fromPage;
        }

        if ($toPage !== null) {
            $args .= ' -l ' . (int) $toPage;
        }

        // '-' ca output scrie la stdout
        $command = escapeshellarg($binary)
            . ' ' . $args
            . ' ' . escapeshellarg($filePath)
            . ' -';

        [$output, $exitCode] = $this->runCommand($command);

        if ($exitCode !== 0) {
            throw PdfProcessingException::fromCommand($command, $exitCode, $output);
        }

        return $this->cleanText($output);
    }

    private function cleanText(string $text): string
    {
        // Strip control characters (preserve newlines and tabs)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Normalize line endings
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        // Collapse consecutive blank lines (max 2)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
