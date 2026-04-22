<?php

namespace Tests\Unit\Output;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\Exceptions\FormatterException;
use App\Services\Output\Formatters\CsvFormatter;
use App\Services\Output\Formatters\ExcelFormatter;
use App\Services\Output\Formatters\JsonFormatter;
use App\Services\Output\OutputFormatterFactory;
use App\Services\Output\Support\DataFlattener;
use App\Services\Output\ValueObjects\FormattedOutput;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use Tests\TestCase;

/**
 * Unit tests for the Output Formatter Layer (Phase 6).
 * Run with: php artisan test --filter=OutputFormatterTest
 */
class OutputFormatterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/documate_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temp files written during tests.
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
    }

    // ─── Factory ─────────────────────────────────────────────────────────────

    public function test_factory_makes_excel_formatter(): void
    {
        $fmt = OutputFormatterFactory::make('excel');
        $this->assertInstanceOf(ExcelFormatter::class, $fmt);
    }

    public function test_factory_makes_csv_formatter(): void
    {
        $fmt = OutputFormatterFactory::make('csv');
        $this->assertInstanceOf(CsvFormatter::class, $fmt);
    }

    public function test_factory_makes_json_formatter(): void
    {
        $fmt = OutputFormatterFactory::make('json');
        $this->assertInstanceOf(JsonFormatter::class, $fmt);
    }

    public function test_factory_throws_for_unknown_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown output format/');

        OutputFormatterFactory::make('xml');
    }

    public function test_factory_lists_three_formats(): void
    {
        $formats = OutputFormatterFactory::availableFormats();

        $this->assertContains('excel', $formats);
        $this->assertContains('csv', $formats);
        $this->assertContains('json', $formats);
        $this->assertCount(3, $formats);
    }

    public function test_factory_default_format_is_excel(): void
    {
        $this->assertEquals('excel', OutputFormatterFactory::defaultFormat());
    }

    // ─── Formatter descriptors ────────────────────────────────────────────────

    public function test_excel_formatter_descriptors(): void
    {
        $fmt = OutputFormatterFactory::make('excel');

        $this->assertEquals('excel', $fmt->getFormat());
        $this->assertEquals('xlsx', $fmt->getExtension());
        $this->assertStringContainsString('spreadsheetml', $fmt->getMimeType());
    }

    public function test_csv_formatter_descriptors(): void
    {
        $fmt = OutputFormatterFactory::make('csv');

        $this->assertEquals('csv', $fmt->getFormat());
        $this->assertEquals('csv', $fmt->getExtension());
        $this->assertStringContainsString('text/csv', $fmt->getMimeType());
    }

    public function test_json_formatter_descriptors(): void
    {
        $fmt = OutputFormatterFactory::make('json');

        $this->assertEquals('json', $fmt->getFormat());
        $this->assertEquals('json', $fmt->getExtension());
        $this->assertEquals('application/json', $fmt->getMimeType());
    }

    // ─── DataFlattener ────────────────────────────────────────────────────────

    public function test_flattener_separates_scalars_from_collections(): void
    {
        $data = [
            'title'      => 'Invoice',
            'total'      => 300.0,
            'line_items' => [
                ['description' => 'Item A', 'total' => 100],
                ['description' => 'Item B', 'total' => 200],
            ],
        ];

        $result = (new DataFlattener())->flatten($data);

        $this->assertArrayHasKey('title', $result['scalars']);
        $this->assertArrayHasKey('total', $result['scalars']);
        $this->assertArrayHasKey('line_items', $result['collections']);
    }

    public function test_flattener_uses_dot_notation_for_nested_objects(): void
    {
        $data = [
            'vendor' => ['name' => 'Acme', 'vat_id' => 'RO123'],
        ];

        $result = (new DataFlattener())->flatten($data);

        $this->assertArrayHasKey('vendor.name', $result['scalars']);
        $this->assertArrayHasKey('vendor.vat_id', $result['scalars']);
        $this->assertEquals('Acme', $result['scalars']['vendor.name']);
    }

    public function test_flattener_collection_first_row_is_headers(): void
    {
        $data = [
            'items' => [
                ['sku' => 'A01', 'qty' => 2],
                ['sku' => 'B02', 'qty' => 5],
            ],
        ];

        $result = (new DataFlattener())->flatten($data);
        $table  = $result['collections']['items'];

        $this->assertEquals(['sku', 'qty'], $table[0]);
        $this->assertEquals(['A01', 2], $table[1]);
        $this->assertEquals(['B02', 5], $table[2]);
    }

    public function test_flattener_collection_merges_keys_from_all_rows(): void
    {
        // Second row has an extra key not present in the first.
        $data = [
            'items' => [
                ['name' => 'A'],
                ['name' => 'B', 'extra' => 'X'],
            ],
        ];

        $result  = (new DataFlattener())->flatten($data);
        $headers = $result['collections']['items'][0];

        $this->assertContains('name', $headers);
        $this->assertContains('extra', $headers);
    }

    public function test_flattener_handles_tables_structure(): void
    {
        $data = [
            'tables' => [
                [
                    'title'   => 'Sales',
                    'headers' => ['Product', 'Qty'],
                    'rows'    => [['Widget', 10], ['Gadget', 5]],
                ],
            ],
        ];

        $result = (new DataFlattener())->flatten($data);

        $this->assertArrayHasKey('Sales', $result['collections']);
        $this->assertEquals(['Product', 'Qty'], $result['collections']['Sales'][0]);
        $this->assertEquals(['Widget', 10], $result['collections']['Sales'][1]);
    }

    public function test_flattener_joins_scalar_arrays_as_string(): void
    {
        $data = ['tags' => ['urgent', 'vat', 'q1']];

        $result = (new DataFlattener())->flatten($data);

        $this->assertEquals('urgent, vat, q1', $result['scalars']['tags']);
    }

    public function test_flattener_returns_empty_collections_when_all_flat(): void
    {
        $data = ['a' => 1, 'b' => 'hello', 'c' => null];

        $result = (new DataFlattener())->flatten($data);

        $this->assertEmpty($result['collections']);
        $this->assertCount(3, $result['scalars']);
    }

    // ─── JsonFormatter ────────────────────────────────────────────────────────

    public function test_json_formatter_writes_file(): void
    {
        $result = $this->makeResult(['invoice_number' => 'INV-001', 'total' => 300.0]);
        $output = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);

        $this->assertInstanceOf(FormattedOutput::class, $output);
        $this->assertFileExists($output->absolutePath);
        $this->assertStringEndsWith('.json', $output->filename);
    }

    public function test_json_formatter_output_is_valid_json(): void
    {
        $result = $this->makeResult(['total' => 99.5]);
        $output = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);

        $decoded = json_decode(file_get_contents($output->absolutePath), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertArrayHasKey('metadata', $decoded);
    }

    public function test_json_formatter_data_section_matches_result_data(): void
    {
        $result  = $this->makeResult(['invoice_number' => 'INV-999']);
        $output  = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);
        $decoded = json_decode(file_get_contents($output->absolutePath), true);

        $this->assertEquals('INV-999', $decoded['data']['invoice_number']);
    }

    public function test_json_formatter_metadata_section_has_required_keys(): void
    {
        $result  = $this->makeResult([]);
        $output  = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);
        $decoded = json_decode(file_get_contents($output->absolutePath), true);
        $meta    = $decoded['metadata'];

        $this->assertArrayHasKey('template', $meta);
        $this->assertArrayHasKey('provider', $meta);
        $this->assertArrayHasKey('total_ms', $meta);
        $this->assertArrayHasKey('extracted_at', $meta);
    }

    public function test_json_formatter_file_size_is_correct(): void
    {
        $result = $this->makeResult(['key' => 'value']);
        $output = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);

        $this->assertEquals(filesize($output->absolutePath), $output->fileSizeBytes);
        $this->assertGreaterThan(0, $output->fileSizeBytes);
    }

    // ─── CsvFormatter ─────────────────────────────────────────────────────────

    public function test_csv_formatter_writes_file(): void
    {
        $result = $this->makeResult(['invoice_number' => 'INV-001']);
        $output = OutputFormatterFactory::make('csv')->format($result, $this->tmpDir);

        $this->assertFileExists($output->absolutePath);
        $this->assertStringEndsWith('.csv', $output->filename);
    }

    public function test_csv_formatter_file_starts_with_utf8_bom(): void
    {
        $result  = $this->makeResult(['key' => 'value']);
        $output  = OutputFormatterFactory::make('csv')->format($result, $this->tmpDir);
        $content = file_get_contents($output->absolutePath);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_csv_formatter_contains_scalar_data(): void
    {
        $result  = $this->makeResult(['invoice_number' => 'INV-777', 'total' => 150.0]);
        $output  = OutputFormatterFactory::make('csv')->format($result, $this->tmpDir);
        $content = file_get_contents($output->absolutePath);

        $this->assertStringContainsString('INV-777', $content);
        $this->assertStringContainsString('150', $content);
    }

    public function test_csv_formatter_contains_collection_section_header(): void
    {
        $result = $this->makeResult([
            'items' => [
                ['name' => 'Widget', 'qty' => 5],
            ],
        ]);
        $output  = OutputFormatterFactory::make('csv')->format($result, $this->tmpDir);
        $content = file_get_contents($output->absolutePath);

        $this->assertStringContainsString('## items', $content);
        $this->assertStringContainsString('Widget', $content);
    }

    public function test_csv_formatter_collection_rows_are_parseable(): void
    {
        $result = $this->makeResult([
            'items' => [
                ['product' => 'A', 'price' => 10],
                ['product' => 'B', 'price' => 20],
            ],
        ]);
        $output  = OutputFormatterFactory::make('csv')->format($result, $this->tmpDir);
        $content = file_get_contents($output->absolutePath);

        // Strip BOM then parse all lines to ensure valid CSV.
        $content = ltrim($content, "\xEF\xBB\xBF");
        $rows    = array_map('str_getcsv', array_filter(explode("\n", trim($content))));

        $this->assertNotEmpty($rows);
    }

    // ─── ExcelFormatter ───────────────────────────────────────────────────────

    public function test_excel_formatter_writes_xlsx_file(): void
    {
        $result = $this->makeResult(['invoice_number' => 'INV-001', 'total' => 300.0]);
        $output = OutputFormatterFactory::make('excel')->format($result, $this->tmpDir);

        $this->assertFileExists($output->absolutePath);
        $this->assertStringEndsWith('.xlsx', $output->filename);
        $this->assertGreaterThan(0, $output->fileSizeBytes);
    }

    public function test_excel_formatter_file_is_valid_zip(): void
    {
        // .xlsx files are ZIP archives — verify the magic bytes.
        $result = $this->makeResult(['field' => 'value']);
        $output = OutputFormatterFactory::make('excel')->format($result, $this->tmpDir);

        $fh     = fopen($output->absolutePath, 'rb');
        $magic  = fread($fh, 4);
        fclose($fh);

        $this->assertEquals("PK\x03\x04", $magic, 'XLSX file should start with ZIP magic bytes');
    }

    public function test_excel_formatter_produces_correct_mime_type(): void
    {
        $result = $this->makeResult([]);
        $output = OutputFormatterFactory::make('excel')->format($result, $this->tmpDir);

        $this->assertStringContainsString('spreadsheetml', $output->mimeType);
    }

    public function test_excel_formatter_with_collections(): void
    {
        $result = $this->makeResult([
            'invoice_number' => 'INV-001',
            'line_items' => [
                ['description' => 'Consulting', 'total' => 500],
                ['description' => 'Support',    'total' => 200],
            ],
        ]);
        $output = OutputFormatterFactory::make('excel')->format($result, $this->tmpDir);

        $this->assertFileExists($output->absolutePath);
        $this->assertGreaterThan(0, $output->fileSizeBytes);
    }

    // ─── FormattedOutput value object ─────────────────────────────────────────

    public function test_formatted_output_file_size_mb(): void
    {
        $output = new FormattedOutput(
            absolutePath:  '/tmp/test.json',
            filename:      'test.json',
            mimeType:      'application/json',
            format:        'json',
            fileSizeBytes: 2 * 1024 * 1024,
        );

        $this->assertEquals(2.0, $output->fileSizeMb());
    }

    public function test_formatted_output_to_array_has_required_keys(): void
    {
        $output = new FormattedOutput('/tmp/f', 'f.json', 'application/json', 'json', 512);
        $array  = $output->toArray();

        $this->assertArrayHasKey('filename', $array);
        $this->assertArrayHasKey('mime_type', $array);
        $this->assertArrayHasKey('format', $array);
        $this->assertArrayHasKey('file_size_bytes', $array);
    }

    // ─── Formatter exception ──────────────────────────────────────────────────

    public function test_formatter_throws_when_directory_not_writable(): void
    {
        $this->expectException(FormatterException::class);

        // /proc is a virtual filesystem — subdirectory creation always fails there.
        OutputFormatterFactory::make('json')->format(
            $this->makeResult([]),
            '/proc/documate_no_write_' . uniqid(),
        );
    }

    // ─── Filename generation ──────────────────────────────────────────────────

    public function test_filename_contains_template_slug(): void
    {
        $result = $this->makeResult([], slug: 'invoice');
        $output = OutputFormatterFactory::make('json')->format($result, $this->tmpDir);

        $this->assertStringContainsString('invoice', $output->filename);
    }

    public function test_filename_contains_correct_extension(): void
    {
        foreach (['excel' => 'xlsx', 'csv' => 'csv', 'json' => 'json'] as $format => $ext) {
            $result = $this->makeResult([]);
            $output = OutputFormatterFactory::make($format)->format($result, $this->tmpDir);

            $this->assertStringEndsWith(".{$ext}", $output->filename, "Format {$format} should use .{$ext}");
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeResult(array $data, string $slug = 'invoice'): ExtractionResult
    {
        $metadata = new PdfMetadata(
            filePath:        '/tmp/test.pdf',
            pageCount:       2,
            fileSizeBytes:   1024 * 50,
            isEncrypted:     false,
            isLikelyScanned: false,
        );

        return new ExtractionResult(
            data:            $data,
            templateSlug:    $slug,
            pdfMetadata:     $metadata,
            aiMetadata:      [
                'provider_used' => 'ollama',
                'model_used'    => 'mistral',
                'input_tokens'  => 120,
                'output_tokens' => 80,
                'total_tokens'  => 200,
                'latency_ms'    => 1500.0,
                'used_fallback' => false,
            ],
            pdfProcessingMs: 80.0,
            aiProcessingMs:  1500.0,
        );
    }
}
