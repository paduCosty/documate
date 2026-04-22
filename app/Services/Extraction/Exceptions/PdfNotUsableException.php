<?php

namespace App\Services\Extraction\Exceptions;

/**
 * Thrown when the PDF cannot be processed before calling the AI.
 * Catching this saves unnecessary AI token spend.
 */
class PdfNotUsableException extends ExtractionException
{
    public static function empty(string $filename): static
    {
        return new static(
            "No text could be extracted from \"{$filename}\". "
            . "The PDF may be image-only (scanned). Install Tesseract OCR to process scanned PDFs."
        );
    }

    public static function tooManyPages(int $pageCount, int $maxPages): static
    {
        return new static(
            "PDF has {$pageCount} pages, which exceeds the maximum of {$maxPages}. "
            . "Split the PDF and extract each part separately."
        );
    }

    public static function encrypted(string $filename): static
    {
        return new static(
            "The PDF \"{$filename}\" is encrypted/password-protected and cannot be processed. "
            . "Remove the password protection and try again."
        );
    }
}
