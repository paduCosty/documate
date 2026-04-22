<?php

namespace App\Services\Output;

use App\Services\Output\Contracts\OutputFormatterInterface;
use App\Services\Output\Formatters\CsvFormatter;
use App\Services\Output\Formatters\ExcelFormatter;
use App\Services\Output\Formatters\JsonFormatter;
use App\Services\Output\Support\DataFlattener;

/**
 * Registry and factory for output formatters.
 *
 * To add a new format:
 *   1. Implement OutputFormatterInterface
 *   2. Add an entry to FORMATTERS below
 *   3. Add the format key to the extraction_jobs migration's output_format enum if needed
 */
class OutputFormatterFactory
{
    /**
     * Maps format keys to formatter classes.
     * Formatters that need DataFlattener receive it via make().
     */
    private const FORMATTERS = [
        'excel' => ExcelFormatter::class,
        'csv'   => CsvFormatter::class,
        'json'  => JsonFormatter::class,
    ];

    /**
     * Instantiates a formatter for the given format key.
     *
     * @throws \InvalidArgumentException for unknown formats
     */
    public static function make(string $format): OutputFormatterInterface
    {
        if (! array_key_exists($format, self::FORMATTERS)) {
            throw new \InvalidArgumentException(
                "Unknown output format \"{$format}\". "
                . 'Available: ' . implode(', ', array_keys(self::FORMATTERS))
            );
        }

        $flattener = new DataFlattener();

        return match ($format) {
            'excel' => new ExcelFormatter($flattener),
            'csv'   => new CsvFormatter($flattener),
            'json'  => new JsonFormatter(),
            default => new (self::FORMATTERS[$format])(),
        };
    }

    /** @return string[] */
    public static function availableFormats(): array
    {
        return array_keys(self::FORMATTERS);
    }

    /**
     * Returns the default format (used when none is specified by the user).
     */
    public static function defaultFormat(): string
    {
        return 'excel';
    }
}
