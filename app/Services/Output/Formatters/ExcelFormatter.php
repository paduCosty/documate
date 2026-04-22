<?php

namespace App\Services\Output\Formatters;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\Exceptions\FormatterException;
use App\Services\Output\Support\DataFlattener;
use App\Services\Output\ValueObjects\FormattedOutput;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Produces a .xlsx workbook from an ExtractionResult.
 *
 * Sheet layout:
 *   - Sheet "Info"             — scalar fields as two-column key/value table (always present)
 *   - Sheet per collection     — one sheet per array-of-objects or extracted table
 *
 * Headers are bold + light-gray background. Columns auto-sized.
 */
class ExcelFormatter extends AbstractFormatter
{
    public function __construct(
        private readonly DataFlattener $flattener,
    ) {}

    public function getFormat(): string    { return 'excel'; }
    public function getMimeType(): string  { return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; }
    public function getExtension(): string { return 'xlsx'; }

    public function format(ExtractionResult $result, string $outputDir): FormattedOutput
    {
        $this->ensureDirectory($outputDir);

        $filename  = $this->generateFilename($result);
        $path      = $outputDir . '/' . $filename;
        $flattened = $this->flattener->flatten($result->data);

        try {
            $raw = Excel::raw(
                new ExtractionWorkbook($flattened['scalars'], $flattened['collections']),
                \Maatwebsite\Excel\Excel::XLSX,
            );
        } catch (\Throwable $e) {
            throw FormatterException::writeFailed('excel', $path, $e);
        }

        $this->writeContent($path, $raw);

        return new FormattedOutput(
            absolutePath:  $path,
            filename:      $filename,
            mimeType:      $this->getMimeType(),
            format:        $this->getFormat(),
            fileSizeBytes: filesize($path),
        );
    }
}

// ─── Inner export classes (local to this file, no public API) ─────────────────

/**
 * Multi-sheet workbook export.
 * One "Info" sheet for scalars + one sheet per collection.
 */
class ExtractionWorkbook implements WithMultipleSheets
{
    public function __construct(
        private readonly array $scalars,
        private readonly array $collections,
    ) {}

    public function sheets(): array
    {
        $sheets = [new InfoSheet($this->scalars)];

        foreach ($this->collections as $name => $rows) {
            $sheets[] = new CollectionSheet($name, $rows);
        }

        return $sheets;
    }
}

/**
 * "Info" sheet: two-column key / value table for scalar fields.
 */
class InfoSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $scalars) {}

    public function title(): string { return 'Info'; }

    public function array(): array
    {
        $rows = [['Field', 'Value']];

        foreach ($this->scalars as $key => $value) {
            $rows[] = [
                (string) $key,
                $value === null ? '' : (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}

/**
 * A sheet for one collection (array-of-objects or extracted table).
 * Row 0 is treated as the header row.
 */
class CollectionSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly string $name,
        private readonly array  $rows,
    ) {}

    public function title(): string
    {
        // Sheet names are limited to 31 characters and cannot contain special chars.
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', '', $this->name), 0, 31);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        if (empty($this->rows)) {
            return [];
        }

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
