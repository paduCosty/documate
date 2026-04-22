<?php

namespace App\Services\Pdf\Exceptions;

use RuntimeException;

/**
 * Excepție de bază pentru toate erorile din layer-ul PDF.
 *
 * Ierarhie:
 *   PdfProcessingException
 *   ├── PdfNotReadableException   — fișier inexistent, corupt sau criptat
 *   └── OcrNotAvailableException  — Tesseract nu e instalat sau nu răspunde
 */
class PdfProcessingException extends RuntimeException
{
    public static function fromCommand(string $command, int $exitCode, string $output): static
    {
        return new static(
            "PDF processing command failed (exit {$exitCode}).\nCommand: {$command}\nOutput: {$output}"
        );
    }
}
