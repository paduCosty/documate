<?php

namespace App\Services\Pdf\Processors;

use App\Services\Pdf\Exceptions\OcrNotAvailableException;
use App\Services\Pdf\Exceptions\PdfNotReadableException;
use App\Services\Pdf\Exceptions\PdfProcessingException;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;
use Illuminate\Support\Facades\Log;

/**
 * Extracts text from scanned PDFs (image-only) using Tesseract OCR.
 *
 * Flow:
 *   1. Convert each PDF page to a PNG image (via Ghostscript)
 *   2. Run Tesseract on each image
 *   3. Concatenate the text and clean up temporary files
 *
 * System dependencies: tesseract-ocr, ghostscript
 */
class OcrPdfProcessor extends AbstractPdfProcessor
{
    public function getName(): string
    {
        return 'ocr';
    }

    public function isAvailable(): bool
    {
        $path = config('ai.pdf_processor.tesseract_path', '/usr/bin/tesseract');

        return file_exists($path) && is_executable($path);
    }

    public function getMetadata(string $filePath): PdfMetadata
    {
        return $this->parseMetadataFromPdfInfo($filePath);
    }

    public function extract(string $filePath): PdfProcessingResult
    {
        $this->assertAvailable();
        $this->assertFileReadable($filePath);

        $metadata = $this->getMetadata($filePath);

        if ($metadata->isEncrypted) {
            throw PdfNotReadableException::encrypted($filePath);
        }

        [$text, $ms] = $this->timed(fn () => $this->runOcr($filePath, 1, $metadata->pageCount));

        // Mark metadata as scanned — confirmed at this point
        $metadataScanned = new PdfMetadata(
            filePath:        $metadata->filePath,
            pageCount:       $metadata->pageCount,
            fileSizeBytes:   $metadata->fileSizeBytes,
            isEncrypted:     $metadata->isEncrypted,
            isLikelyScanned: true,
            title:           $metadata->title,
            author:          $metadata->author,
            creator:         $metadata->creator,
        );

        return new PdfProcessingResult(
            text:             $text,
            metadata:         $metadataScanned,
            processorUsed:    $this->getName(),
            processingTimeMs: $ms,
        );
    }

    public function extractPages(string $filePath, int $fromPage, int $toPage): PdfProcessingResult
    {
        $this->assertAvailable();
        $this->assertFileReadable($filePath);

        $metadata = $this->getMetadata($filePath);

        [$text, $ms] = $this->timed(fn () => $this->runOcr($filePath, $fromPage, $toPage));

        return new PdfProcessingResult(
            text:             $text,
            metadata:         $metadata,
            processorUsed:    $this->getName(),
            processingTimeMs: $ms,
        );
    }

    // -------------------------------------------------------------------------

    private function assertAvailable(): void
    {
        if (! $this->isAvailable()) {
            throw OcrNotAvailableException::tesseractMissing(
                config('ai.pdf_processor.tesseract_path', '/usr/bin/tesseract')
            );
        }
    }

    private function runOcr(string $filePath, int $fromPage, int $toPage): string
    {
        $tmpDir = $this->createTempDir();

        try {
            $images = $this->convertPagesToImages($filePath, $tmpDir, $fromPage, $toPage);
            return $this->ocrImages($images);
        } finally {
            $this->cleanupDir($tmpDir);
        }
    }

    private function convertPagesToImages(string $filePath, string $tmpDir, int $fromPage, int $toPage): array
    {
        $dpi     = config('ai.pdf_processor.ocr_dpi', 300);
        $pattern = $tmpDir . '/page-%04d.png';

        $command = 'gs -dBATCH -dNOPAUSE -sDEVICE=png16m'
            . ' -r' . (int) $dpi
            . ' -dFirstPage=' . (int) $fromPage
            . ' -dLastPage=' . (int) $toPage
            . ' -sOutputFile=' . escapeshellarg($pattern)
            . ' ' . escapeshellarg($filePath);

        [$output, $exitCode] = $this->runCommand($command);

        if ($exitCode !== 0) {
            throw PdfProcessingException::fromCommand($command, $exitCode, $output);
        }

        $images = glob($tmpDir . '/page-*.png');

        if (empty($images)) {
            throw new PdfProcessingException('Ghostscript produced no images from PDF.');
        }

        sort($images);

        return $images;
    }

    private function ocrImages(array $imagePaths): string
    {
        $tesseract = config('ai.pdf_processor.tesseract_path', '/usr/bin/tesseract');
        $lang      = config('ai.pdf_processor.tesseract_lang', 'ron+eng');
        $pages     = [];

        foreach ($imagePaths as $imagePath) {
            $outputBase = $imagePath . '_ocr';

            $command = escapeshellarg($tesseract)
                . ' ' . escapeshellarg($imagePath)
                . ' ' . escapeshellarg($outputBase)
                . ' -l ' . escapeshellarg($lang)
                . ' --psm 3';

            [$output, $exitCode] = $this->runCommand($command);

            if ($exitCode !== 0) {
                // Log the failed page but continue processing the rest
                Log::warning("[ocr] Tesseract failed on {$imagePath} (exit {$exitCode}): {$output}");
                continue;
            }

            $textFile = $outputBase . '.txt';

            if (file_exists($textFile)) {
                $pages[] = trim(file_get_contents($textFile));
                @unlink($textFile);
            }
        }

        return implode("\n\n", array_filter($pages));
    }

    private function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/documate_ocr_' . uniqid('', true);
        mkdir($dir, 0700, true);

        return $dir;
    }

    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
