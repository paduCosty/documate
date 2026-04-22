<?php

namespace Tests\Feature\Extraction;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\ValueObjects\AiResponse;
use App\Services\Extraction\ExtractionService;
use App\Services\Extraction\Exceptions\PdfNotUsableException;
use App\Services\Extraction\JsonExtractor;
use App\Services\Extraction\JsonValidator;
use App\Services\Extraction\PromptBuilder;
use App\Services\Extraction\ResultNormalizer;
use App\Services\Pdf\Exceptions\PdfProcessingException;
use App\Services\Pdf\PdfProcessorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests that run the full extraction pipeline with real pdftotext.
 * AI provider is always mocked — tests verify PDF processing + normalization.
 *
 * Run with: php artisan test --filter=ExtractionPipelineIntegrationTest
 */
class ExtractionPipelineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/integration_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->seedTemplates();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    // ─── Happy path ───────────────────────────────────────────────────────────

    public function test_full_pipeline_extracts_text_from_real_pdf(): void
    {
        $pdfPath = $this->createTextPdf("Invoice Nr. 12345\nDate: 15.03.2024\nTotal: 1500.00 RON");

        $service = $this->makeService(
            aiText: '{"invoice_number":"12345","invoice_date":"15.03.2024","total":1500.00}'
        );

        $result = $service->extract($pdfPath, 'invoice');

        $this->assertEquals('12345', $result->data['invoice_number']);
        $this->assertEquals('2024-03-15', $result->data['invoice_date']); // normalized
        $this->assertEquals(1500.0, $result->data['total']);
        $this->assertEquals('invoice', $result->templateSlug);
        $this->assertGreaterThan(0, $result->pdfMetadata->pageCount);
        $this->assertGreaterThan(0, $result->pdfProcessingMs);
    }

    public function test_pipeline_normalizes_european_date(): void
    {
        $pdfPath = $this->createTextPdf('Invoice dated 05.11.2023');

        $service = $this->makeService(aiText: '{"invoice_date":"05.11.2023","total":null}');
        $result  = $service->extract($pdfPath, 'invoice');

        $this->assertEquals('2023-11-05', $result->data['invoice_date']);
        $this->assertNull($result->data['total']);
    }

    public function test_pipeline_normalizes_currency(): void
    {
        $pdfPath = $this->createTextPdf('Total: 2.450,75 EUR');

        $service = $this->makeService(aiText: '{"total":"2.450,75 EUR"}');
        $result  = $service->extract($pdfPath, 'invoice');

        $this->assertEquals(2450.75, $result->data['total']);
    }

    public function test_pipeline_normalizes_null_strings(): void
    {
        $pdfPath = $this->createTextPdf('Generic document content');

        $service = $this->makeService(aiText: '{"title":"Report","author":"n/a","date":null}');
        $result  = $service->extract($pdfPath, 'generic');

        $this->assertNull($result->data['author']);
        $this->assertNull($result->data['date']);
    }

    public function test_pipeline_retries_when_ai_returns_invalid_json(): void
    {
        config(['ai.extraction.json_retries' => 3]);

        $pdfPath   = $this->createTextPdf('Test invoice content here');
        $callCount = 0;

        $ai = $this->createMock(AiProviderInterface::class);
        $ai->method('complete')->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            $text = $callCount < 2 ? 'not json garbage text' : '{"invoice_number":"RETRY-OK"}';
            return $this->makeAiResponse($text);
        });
        $ai->method('getName')->willReturn('ollama');

        $service = $this->makeService(aiProvider: $ai);
        $result  = $service->extract($pdfPath, 'invoice');

        $this->assertEquals(2, $callCount);
        $this->assertEquals('RETRY-OK', $result->data['invoice_number']);
    }

    public function test_pipeline_result_metadata_contains_provider_and_tokens(): void
    {
        $pdfPath = $this->createTextPdf('Some PDF content');
        $service = $this->makeService(aiText: '{"field":"value"}');
        $result  = $service->extract($pdfPath, 'generic');

        $this->assertEquals('ollama', $result->aiMetadata['provider_used']);
        $this->assertEquals(150, $result->aiMetadata['total_tokens']);
        $this->assertGreaterThan(0, $result->aiProcessingMs);
    }

    // ─── Guard: empty text ────────────────────────────────────────────────────

    public function test_guard_throws_for_blank_pdf(): void
    {
        $this->expectException(PdfNotUsableException::class);
        $this->expectExceptionMessageMatches('/No text could be extracted/');

        // GS-generated blank page produces empty text
        $blankPdf = $this->createBlankPdf();
        $service  = $this->makeService(aiText: '{}');
        $service->extract($blankPdf, 'invoice');
    }

    public function test_guard_throws_for_too_many_pages(): void
    {
        config(['ai.extraction.max_pages' => 2]);

        $this->expectException(PdfNotUsableException::class);
        $this->expectExceptionMessageMatches('/exceeds the maximum/');

        $pdfPath = $this->createMultiPageTextPdf(3);
        $service = $this->makeService(aiText: '{}');
        $service->extract($pdfPath, 'invoice');
    }

    public function test_corrupt_pdf_throws_pdf_processing_exception(): void
    {
        $this->expectException(PdfProcessingException::class);

        $corruptPdf = $this->tmpDir . '/corrupt.pdf';
        file_put_contents($corruptPdf, 'this is not a pdf at all');

        $service = $this->makeService(aiText: '{}');
        $service->extract($corruptPdf, 'invoice');
    }

    // ─── PdfNotUsableException factory methods ────────────────────────────────

    public function test_exception_empty_message_contains_filename(): void
    {
        $e = PdfNotUsableException::empty('invoice.pdf');

        $this->assertStringContainsString('No text could be extracted', $e->getMessage());
        $this->assertStringContainsString('invoice.pdf', $e->getMessage());
    }

    public function test_exception_too_many_pages_contains_counts(): void
    {
        $e = PdfNotUsableException::tooManyPages(75, 50);

        $this->assertStringContainsString('75', $e->getMessage());
        $this->assertStringContainsString('50', $e->getMessage());
    }

    public function test_exception_encrypted_contains_filename(): void
    {
        $e = PdfNotUsableException::encrypted('secret.pdf');

        $this->assertStringContainsString('encrypted', $e->getMessage());
        $this->assertStringContainsString('secret.pdf', $e->getMessage());
    }

    // ─── Cleanup command ──────────────────────────────────────────────────────

    public function test_cleanup_command_deletes_expired_jobs_and_files(): void
    {
        $outputFile = $this->tmpDir . '/result.json';
        file_put_contents($outputFile, '{"data":{}}');

        $job = \App\Models\ExtractionJob::create([
            'original_filename' => 'old.pdf',
            'status'            => 'completed',
            'output_format'     => 'json',
            'output_path'       => $outputFile,
            'expires_at'        => now()->subHour(),
        ]);

        $this->artisan('extraction:cleanup')
            ->assertSuccessful()
            ->expectsOutputToContain('1 job(s) deleted');

        $this->assertDatabaseMissing('extraction_jobs', ['id' => $job->id]);
        $this->assertFileDoesNotExist($outputFile);
    }

    public function test_cleanup_command_skips_non_expired_jobs(): void
    {
        $job = \App\Models\ExtractionJob::create([
            'original_filename' => 'fresh.pdf',
            'status'            => 'completed',
            'output_format'     => 'json',
            'expires_at'        => now()->addHours(23),
        ]);

        $this->artisan('extraction:cleanup')
            ->assertSuccessful()
            ->expectsOutputToContain('0 job(s) deleted');

        $this->assertDatabaseHas('extraction_jobs', ['id' => $job->id]);
    }

    // ─── Encryption detection ────────────────────────────────────────────────

    public function test_real_pdf_is_not_flagged_as_encrypted(): void
    {
        $pdfPath  = $this->createTextPdf('Test content for encryption check');
        $service  = $this->makeService(aiText: '{"result":"ok"}');
        $result   = $service->extract($pdfPath, 'generic');

        // If we get here without PdfNotReadableException, the encrypted check is working correctly.
        $this->assertFalse($result->pdfMetadata->isEncrypted);
    }

    // ─── Log channel ─────────────────────────────────────────────────────────

    public function test_extraction_log_channel_is_configured(): void
    {
        $channels = config('logging.channels');
        $this->assertArrayHasKey('extraction', $channels);
        $this->assertEquals('daily', $channels['extraction']['driver']);
        $this->assertStringContainsString('extraction.log', $channels['extraction']['path']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeService(
        string               $aiText     = '{}',
        ?AiProviderInterface $aiProvider = null,
    ): ExtractionService {
        if ($aiProvider === null) {
            $aiProvider = $this->createMock(AiProviderInterface::class);
            $aiProvider->method('complete')->willReturn($this->makeAiResponse($aiText));
            $aiProvider->method('getName')->willReturn('ollama');
        }

        return new ExtractionService(
            pdfProcessor:  PdfProcessorFactory::make('native'),
            aiProvider:    $aiProvider,
            promptBuilder: new PromptBuilder(),
            jsonValidator: new JsonValidator(new JsonExtractor()),
            normalizer:    new ResultNormalizer(),
        );
    }

    private function makeAiResponse(string $text): AiResponse
    {
        return new AiResponse(
            text:         $text,
            providerUsed: 'ollama',
            modelUsed:    'mistral',
            inputTokens:  100,
            outputTokens: 50,
            latencyMs:    200.0,
        );
    }

    /** Creates a single-page PDF with the given text using Ghostscript. */
    private function createTextPdf(string $text): string
    {
        $psFile  = $this->tmpDir . '/input.ps';
        $pdfFile = $this->tmpDir . '/output_' . uniqid() . '.pdf';

        // Escape parentheses and backslashes for PostScript string literals.
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

        // Write multi-line text line by line.
        $lines = explode("\n", $escaped);
        $y     = 700;
        $psBody = '';
        foreach ($lines as $line) {
            $psBody .= "72 {$y} moveto ({$line}) show\n";
            $y -= 16;
        }

        $ps = "%!PS\n/Helvetica findfont 11 scalefont setfont\n{$psBody}showpage\n";
        file_put_contents($psFile, $ps);

        exec('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='
            . escapeshellarg($pdfFile) . ' ' . escapeshellarg($psFile) . ' 2>/dev/null');

        return $pdfFile;
    }

    /** Creates a blank page PDF (no text) via Ghostscript — triggers the empty guard. */
    private function createBlankPdf(): string
    {
        $pdfFile = $this->tmpDir . '/blank.pdf';

        exec('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='
            . escapeshellarg($pdfFile) . ' -c "showpage" 2>/dev/null');

        return $pdfFile;
    }

    /** Creates a multi-page PDF with real text, each page triggering max_pages guard. */
    private function createMultiPageTextPdf(int $pages): string
    {
        $psFile  = $this->tmpDir . '/multipage.ps';
        $pdfFile = $this->tmpDir . '/multipage.pdf';

        $ps = "%!PS\n/Helvetica findfont 12 scalefont setfont\n";
        for ($i = 1; $i <= $pages; $i++) {
            $ps .= "72 700 moveto (Page {$i} content here) show\nshowpage\n";
        }
        file_put_contents($psFile, $ps);

        exec('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='
            . escapeshellarg($pdfFile) . ' ' . escapeshellarg($psFile) . ' 2>/dev/null');

        return $pdfFile;
    }

    private function seedTemplates(): void
    {
        foreach (['invoice', 'generic'] as $slug) {
            DB::table('extraction_templates')->updateOrInsert(
                ['slug' => $slug, 'user_id' => null],
                [
                    'name'            => ucfirst($slug),
                    'prompt_template' => 'Extract from: {pdf_text}. Schema: {output_schema}',
                    'output_schema'   => json_encode(['type' => 'object']),
                    'is_system'       => true,
                    'active'          => true,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }
}
