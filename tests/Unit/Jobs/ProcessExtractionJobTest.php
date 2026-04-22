<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessExtractionJob;
use App\Models\ExtractionJob;
use App\Models\ExtractionTemplate;
use App\Services\Ai\ValueObjects\AiResponse;
use App\Services\Extraction\ExtractionService;
use App\Services\Extraction\Exceptions\JsonExtractionFailedException;
use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\OutputFormatterFactory;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit tests for ProcessExtractionJob (Phase 7).
 * Run with: php artisan test --filter=ProcessExtractionJobTest
 */
class ProcessExtractionJobTest extends TestCase
{
    use RefreshDatabase;

    private string $tmpDir;
    private string $fakePdfPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir      = sys_get_temp_dir() . '/pej_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->fakePdfPath = $this->tmpDir . '/test.pdf';
        file_put_contents($this->fakePdfPath, '%PDF-1.4 fake');

        $this->seedTemplate();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The job may have already deleted the temp file — suppress errors.
        if (file_exists($this->fakePdfPath)) {
            @unlink($this->fakePdfPath);
        }
        if (is_dir($this->tmpDir) && count(scandir($this->tmpDir)) === 2) {
            @rmdir($this->tmpDir);
        }
    }

    // ─── ExtractionJob model ──────────────────────────────────────────────────

    public function test_extraction_job_uuid_is_set_on_create(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $this->assertNotEmpty($job->uuid);
    }

    public function test_extraction_job_expires_at_defaults_to_24h(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $this->assertTrue($job->expires_at->isAfter(now()->addHours(23)));
    }

    public function test_extraction_job_mark_processing(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $job->markProcessing();

        $this->assertEquals('processing', $job->fresh()->status);
        $this->assertTrue($job->isProcessing());
    }

    public function test_extraction_job_mark_completed(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $job->markCompleted(
            outputPath:       '/tmp/result.xlsx',
            extractedData:    ['invoice_number' => 'INV-001'],
            tokensUsed:       150,
            processingTimeMs: 3200,
            pageCount:        2,
        );

        $fresh = $job->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals('/tmp/result.xlsx', $fresh->output_path);
        $this->assertEquals(['invoice_number' => 'INV-001'], $fresh->extracted_data);
        $this->assertEquals(150, $fresh->tokens_used);
        $this->assertEquals(2, $fresh->page_count);
        $this->assertNotNull($fresh->processed_at);
        $this->assertTrue($job->isCompleted());
    }

    public function test_extraction_job_mark_failed(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $job->markFailed('AI timed out');

        $fresh = $job->fresh();
        $this->assertEquals('failed', $fresh->status);
        $this->assertEquals('AI timed out', $fresh->error_message);
        $this->assertNotNull($fresh->processed_at);
        $this->assertTrue($job->isFailed());
    }

    public function test_extraction_job_is_expired(): void
    {
        $job             = ExtractionJob::create($this->jobAttributes());
        $job->expires_at = now()->subHour();
        $job->save();

        $this->assertTrue($job->fresh()->isExpired());
    }

    public function test_extraction_job_is_not_expired_when_fresh(): void
    {
        $job = ExtractionJob::create($this->jobAttributes());
        $this->assertFalse($job->isExpired());
    }

    public function test_extraction_job_storage_path_for_user(): void
    {
        $job          = new ExtractionJob();
        $job->uuid    = 'test-uuid-123';
        $job->user_id = 42;

        $this->assertEquals('extractions/u42/test-uuid-123', $job->storagePath());
    }

    public function test_extraction_job_storage_path_for_guest(): void
    {
        $job          = new ExtractionJob();
        $job->uuid    = 'test-uuid-456';
        $job->user_id = null;

        $this->assertEquals('extractions/guest/test-uuid-456', $job->storagePath());
    }

    public function test_extraction_job_pending_scope(): void
    {
        ExtractionJob::create($this->jobAttributes(['status' => 'pending']));
        ExtractionJob::create($this->jobAttributes(['status' => 'completed']));

        $pending = ExtractionJob::pending()->get();

        $this->assertEquals(1, $pending->count());
        $this->assertEquals('pending', $pending->first()->status);
    }

    // ─── ProcessExtractionJob ─────────────────────────────────────────────────

    public function test_job_marks_record_as_processing_then_completed(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockSuccessfulService();

        $this->dispatchJob($dbJob, $service);

        $fresh = $dbJob->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertNotNull($fresh->output_path);
        $this->assertNotNull($fresh->processed_at);
        $this->assertIsArray($fresh->extracted_data);
    }

    public function test_job_stores_token_count(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockSuccessfulService(totalTokens: 250);

        $this->dispatchJob($dbJob, $service);

        $this->assertEquals(250, $dbJob->fresh()->tokens_used);
    }

    public function test_job_stores_page_count(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockSuccessfulService(pageCount: 5);

        $this->dispatchJob($dbJob, $service);

        $this->assertEquals(5, $dbJob->fresh()->page_count);
    }

    public function test_job_marks_failed_on_extraction_exception(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockFailingService('AI never returned valid JSON');

        $this->expectException(\Throwable::class);

        $this->dispatchJob($dbJob, $service);

        $this->assertEquals('failed', $dbJob->fresh()->status);
        $this->assertStringContainsString('AI never returned', $dbJob->fresh()->error_message);
    }

    public function test_job_deletes_temp_file_after_success(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockSuccessfulService();

        $this->assertTrue(file_exists($this->fakePdfPath));

        $this->dispatchJob($dbJob, $service);

        $this->assertFalse(file_exists($this->fakePdfPath));
    }

    public function test_job_deletes_temp_file_after_failure(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockFailingService('Error');

        $this->assertTrue(file_exists($this->fakePdfPath));

        try {
            $this->dispatchJob($dbJob, $service);
        } catch (\Throwable) {
            // Expected
        }

        $this->assertFalse(file_exists($this->fakePdfPath));
    }

    public function test_job_output_file_exists_after_success(): void
    {
        $dbJob   = ExtractionJob::create($this->jobAttributes());
        $service = $this->mockSuccessfulService();

        $this->dispatchJob($dbJob, $service);

        $outputPath = $dbJob->fresh()->output_path;
        $this->assertNotNull($outputPath);
        $this->assertFileExists($outputPath);

        // Cleanup
        @unlink($outputPath);
        @rmdir(dirname($outputPath));
    }

    public function test_job_failed_hook_marks_record_if_not_already_failed(): void
    {
        $dbJob = ExtractionJob::create($this->jobAttributes());

        $job = new ProcessExtractionJob(
            extractionJob:    $dbJob,
            tempFilePath:     $this->fakePdfPath,
            templateSlug:     'invoice',
            outputFormat:     'json',
            providerOverride: null,
        );

        $job->failed(new \RuntimeException('Unexpected crash'));

        $this->assertEquals('failed', $dbJob->fresh()->status);
        // RuntimeException maps to the generic friendly message in toFriendlyMessage().
        $this->assertStringContainsString('unexpected error', $dbJob->fresh()->error_message);
    }

    public function test_job_failed_hook_skips_if_already_failed(): void
    {
        $dbJob = ExtractionJob::create($this->jobAttributes());
        $dbJob->markFailed('Original error');

        $job = new ProcessExtractionJob(
            extractionJob:    $dbJob,
            tempFilePath:     $this->fakePdfPath,
            templateSlug:     'invoice',
            outputFormat:     'json',
            providerOverride: null,
        );

        // Should not overwrite the original error message.
        $job->failed(new \RuntimeException('Second error'));

        $this->assertEquals('Original error', $dbJob->fresh()->error_message);
    }

    public function test_job_supports_all_output_formats(): void
    {
        foreach (['excel', 'csv', 'json'] as $format) {
            $dbJob   = ExtractionJob::create($this->jobAttributes(['output_format' => $format]));
            $service = $this->mockSuccessfulService();

            $this->dispatchJob($dbJob, $service, format: $format);

            $this->assertEquals('completed', $dbJob->fresh()->status,
                "Format {$format} should result in completed status");

            $outputPath = $dbJob->fresh()->output_path;
            $this->assertStringEndsWith(
                OutputFormatterFactory::make($format)->getExtension(),
                $outputPath,
                "Format {$format} should produce correct file extension"
            );

            // Cleanup
            @unlink($outputPath);
            @rmdir(dirname($outputPath));
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function jobAttributes(array $overrides = []): array
    {
        return array_merge([
            'original_filename' => 'invoice.pdf',
            'file_size_bytes'   => 1024 * 50,
            'status'            => 'pending',
            'output_format'     => 'json',
            'expires_at'        => now()->addHours(24),
        ], $overrides);
    }

    private function makeExtractionResult(int $totalTokens = 200, int $pageCount = 2): ExtractionResult
    {
        $metadata = new PdfMetadata(
            filePath:        $this->fakePdfPath,
            pageCount:       $pageCount,
            fileSizeBytes:   1024 * 50,
            isEncrypted:     false,
            isLikelyScanned: false,
        );

        return new ExtractionResult(
            data:            ['invoice_number' => 'INV-001', 'total' => 300.0],
            templateSlug:    'invoice',
            pdfMetadata:     $metadata,
            aiMetadata:      [
                'provider_used' => 'ollama',
                'model_used'    => 'mistral',
                'input_tokens'  => $totalTokens - 50,
                'output_tokens' => 50,
                'total_tokens'  => $totalTokens,
                'latency_ms'    => 1200.0,
                'used_fallback' => false,
            ],
            pdfProcessingMs: 80.0,
            aiProcessingMs:  1200.0,
        );
    }

    private function mockSuccessfulService(int $totalTokens = 200, int $pageCount = 2): ExtractionService
    {
        $service = $this->createMock(ExtractionService::class);
        $service->method('extract')
            ->willReturn($this->makeExtractionResult($totalTokens, $pageCount));

        return $service;
    }

    private function mockFailingService(string $message): ExtractionService
    {
        $service = $this->createMock(ExtractionService::class);
        $service->method('extract')
            ->willThrowException(
                JsonExtractionFailedException::afterRetries(3, 'bad response')
            );

        return $service;
    }

    private function dispatchJob(
        ExtractionJob    $dbJob,
        ExtractionService $service,
        string            $format = 'json',
    ): void {
        $job = new ProcessExtractionJob(
            extractionJob:    $dbJob,
            tempFilePath:     $this->fakePdfPath,
            templateSlug:     'invoice',
            outputFormat:     $format,
            providerOverride: null,
        );

        $job->handle($service);
    }

    private function seedTemplate(): void
    {
        \Illuminate\Support\Facades\DB::table('extraction_templates')->updateOrInsert(
            ['slug' => 'invoice', 'user_id' => null],
            [
                'name'            => 'Invoice',
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
