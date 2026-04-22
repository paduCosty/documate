<?php

namespace App\Services\Pdf;

use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\Processors\AutoPdfProcessor;
use App\Services\Pdf\Processors\NativePdfProcessor;
use App\Services\Pdf\Processors\OcrPdfProcessor;

/**
 * Factory for PDF text processors.
 *
 * To add a new processor:
 *   1. Implement PdfProcessorInterface
 *   2. Add an entry to the PROCESSORS map below
 *   3. Add the driver name to config/ai.php → pdf_processor.driver
 */
class PdfProcessorFactory
{
    /**
     * Registru de procesori disponibili.
     * Cheile corespund valorilor config('ai.pdf_processor.driver').
     */
    private const PROCESSORS = [
        'native' => NativePdfProcessor::class,
        'ocr'    => OcrPdfProcessor::class,
        'auto'   => AutoPdfProcessor::class,
    ];

    /**
     * Returnează procesorul configurat în config/ai.php → pdf_processor.driver.
     */
    public static function fromConfig(): PdfProcessorInterface
    {
        return static::make(
            config('ai.pdf_processor.driver', 'auto')
        );
    }

    /**
     * Returnează un procesor specific după slug.
     *
     * @throws \InvalidArgumentException pentru drivere necunoscute
     */
    public static function make(string $driver): PdfProcessorInterface
    {
        if (! array_key_exists($driver, self::PROCESSORS)) {
            throw new \InvalidArgumentException(
                "Unknown PDF processor driver \"{$driver}\". "
                . 'Available: ' . implode(', ', array_keys(self::PROCESSORS))
            );
        }

        return match ($driver) {
            'auto'   => new AutoPdfProcessor(new NativePdfProcessor(), new OcrPdfProcessor()),
            default  => new (self::PROCESSORS[$driver])(),
        };
    }

    /**
     * Returnează lista de drivere disponibile.
     *
     * @return string[]
     */
    public static function availableDrivers(): array
    {
        return array_keys(self::PROCESSORS);
    }
}
