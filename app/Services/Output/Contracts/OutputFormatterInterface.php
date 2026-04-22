<?php

namespace App\Services\Output\Contracts;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\ValueObjects\FormattedOutput;

/**
 * Contract for all output formatters.
 *
 * To add a new format:
 *   1. Implement this interface
 *   2. Register the class in OutputFormatterFactory::FORMATTERS
 *   3. Add the format key to config/ai.php if needed
 */
interface OutputFormatterInterface
{
    /**
     * Formats an ExtractionResult and writes the output file to disk.
     *
     * @param  ExtractionResult $result    The normalized extraction data.
     * @param  string           $outputDir Absolute path to the directory where the file will be written.
     * @return FormattedOutput             Immutable descriptor of the written file.
     *
     * @throws \App\Services\Output\Exceptions\FormatterException
     */
    public function format(ExtractionResult $result, string $outputDir): FormattedOutput;

    /** Unique format key, e.g. "excel", "csv", "json". */
    public function getFormat(): string;

    /** MIME type for HTTP Content-Type and download headers. */
    public function getMimeType(): string;

    /** File extension without leading dot, e.g. "xlsx". */
    public function getExtension(): string;
}
