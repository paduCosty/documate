<?php

namespace App\Services\Output\Formatters;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\Exceptions\FormatterException;
use App\Services\Output\Support\DataFlattener;
use App\Services\Output\ValueObjects\FormattedOutput;

/**
 * Produces a UTF-8 .csv file from an ExtractionResult.
 *
 * File layout (all in one file):
 *   Section "Info"          — two-column key/value table for scalar fields
 *   [blank line separator]
 *   Section per collection  — each array-of-objects / table as its own CSV block,
 *                             preceded by a "## CollectionName" comment header
 *
 * UTF-8 BOM is prepended so Excel opens the file with correct encoding on Windows.
 */
class CsvFormatter extends AbstractFormatter
{
    private const UTF8_BOM       = "\xEF\xBB\xBF";
    private const SECTION_PREFIX = '##';

    public function __construct(
        private readonly DataFlattener $flattener,
    ) {}

    public function getFormat(): string    { return 'csv'; }
    public function getMimeType(): string  { return 'text/csv; charset=UTF-8'; }
    public function getExtension(): string { return 'csv'; }

    public function format(ExtractionResult $result, string $outputDir): FormattedOutput
    {
        $this->ensureDirectory($outputDir);

        $filename  = $this->generateFilename($result);
        $path      = $outputDir . '/' . $filename;
        $flattened = $this->flattener->flatten($result->data);

        try {
            $content = $this->buildContent($flattened['scalars'], $flattened['collections']);
        } catch (\Throwable $e) {
            throw FormatterException::writeFailed('csv', $path, $e);
        }

        $this->writeContent($path, self::UTF8_BOM . $content);

        return new FormattedOutput(
            absolutePath:  $path,
            filename:      $filename,
            mimeType:      $this->getMimeType(),
            format:        $this->getFormat(),
            fileSizeBytes: filesize($path),
        );
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function buildContent(array $scalars, array $collections): string
    {
        $buffer = fopen('php://memory', 'r+');

        // Info section.
        $this->writeRow($buffer, [self::SECTION_PREFIX . ' Info']);
        $this->writeRow($buffer, ['Field', 'Value']);

        foreach ($scalars as $key => $value) {
            $this->writeRow($buffer, [(string) $key, $this->renderScalar($value)]);
        }

        // Collection sections.
        foreach ($collections as $name => $rows) {
            $this->writeRow($buffer, []);  // blank separator
            $this->writeRow($buffer, [self::SECTION_PREFIX . ' ' . $name]);

            foreach ($rows as $row) {
                $this->writeRow($buffer, array_map(
                    fn ($cell) => $cell === null ? '' : (string) $cell,
                    $row,
                ));
            }
        }

        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        return $content;
    }

    private function writeRow($handle, array $row): void
    {
        fputcsv($handle, $row, separator: ',', enclosure: '"', escape: '\\');
    }
}
