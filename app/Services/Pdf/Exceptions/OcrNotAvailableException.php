<?php

namespace App\Services\Pdf\Exceptions;

class OcrNotAvailableException extends PdfProcessingException
{
    public static function tesseractMissing(string $path): static
    {
        return new static("Tesseract OCR binary not found at: {$path}. Install tesseract-ocr to process scanned PDFs.");
    }

    public static function languagePackMissing(string $lang): static
    {
        return new static("Tesseract language pack \"{$lang}\" is not installed. Run: apt-get install tesseract-ocr-{$lang}");
    }
}
