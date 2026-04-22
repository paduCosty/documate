<?php

namespace App\Services\Output\Formatters;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\Contracts\OutputFormatterInterface;
use App\Services\Output\Exceptions\FormatterException;

/**
 * Shared utilities for all formatters.
 * Subclasses only implement writeFile() and the three descriptor methods.
 */
abstract class AbstractFormatter implements OutputFormatterInterface
{
    /**
     * Generates a sanitized filename: "{template}_{date}_{time}.{ext}"
     * Example: "invoice_2024-03-15_143022.xlsx"
     */
    protected function generateFilename(ExtractionResult $result): string
    {
        $slug      = preg_replace('/[^a-z0-9_-]/i', '_', $result->templateSlug);
        $timestamp = now()->format('Y-m-d_His');

        return "{$slug}_{$timestamp}.{$this->getExtension()}";
    }

    /**
     * Ensures the output directory exists and is writable.
     *
     * @throws FormatterException
     */
    protected function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            if (! is_writable($dir)) {
                throw FormatterException::directoryNotWritable($dir);
            }
            return;
        }

        // Suppress the PHP warning — we check the return value ourselves.
        if (! @mkdir($dir, 0755, recursive: true) && ! is_dir($dir)) {
            throw FormatterException::directoryNotWritable($dir);
        }

        if (! is_writable($dir)) {
            throw FormatterException::directoryNotWritable($dir);
        }
    }

    /**
     * Writes $content to $path and returns the byte count.
     *
     * @throws FormatterException
     */
    protected function writeContent(string $path, string $content): int
    {
        $bytes = file_put_contents($path, $content);

        if ($bytes === false) {
            throw new FormatterException("Failed to write file: {$path}");
        }

        return $bytes;
    }

    /**
     * Renders a scalar key/value pair for metadata sections.
     * Converts null → empty string, booleans → "yes"/"no".
     */
    protected function renderScalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }
}
