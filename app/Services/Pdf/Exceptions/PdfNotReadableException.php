<?php

namespace App\Services\Pdf\Exceptions;

class PdfNotReadableException extends PdfProcessingException
{
    public static function fileNotFound(string $path): static
    {
        return new static("PDF file not found: {$path}");
    }

    public static function fileNotReadable(string $path): static
    {
        return new static("PDF file is not readable: {$path}");
    }

    public static function encrypted(string $path): static
    {
        return new static("PDF is encrypted and cannot be processed: {$path}");
    }
}
