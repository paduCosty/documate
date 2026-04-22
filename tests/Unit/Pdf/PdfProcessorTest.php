<?php

namespace Tests\Unit\Pdf;

use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\Exceptions\OcrNotAvailableException;
use App\Services\Pdf\Exceptions\PdfNotReadableException;
use App\Services\Pdf\Exceptions\PdfProcessingException;
use App\Services\Pdf\PdfProcessorFactory;
use App\Services\Pdf\Processors\AutoPdfProcessor;
use App\Services\Pdf\Processors\NativePdfProcessor;
use App\Services\Pdf\Processors\OcrPdfProcessor;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;
use Tests\TestCase;

/**
 * Teste unitare pentru PDF Processor Layer (Faza 3).
 * Rulează cu: php artisan test --filter=PdfProcessorTest
 */
class PdfProcessorTest extends TestCase
{
    // ─── Factory ─────────────────────────────────────────────────────────────

    public function test_factory_returns_native_processor(): void
    {
        $processor = PdfProcessorFactory::make('native');

        $this->assertInstanceOf(NativePdfProcessor::class, $processor);
        $this->assertEquals('native', $processor->getName());
    }

    public function test_factory_returns_ocr_processor(): void
    {
        $processor = PdfProcessorFactory::make('ocr');

        $this->assertInstanceOf(OcrPdfProcessor::class, $processor);
        $this->assertEquals('ocr', $processor->getName());
    }

    public function test_factory_returns_auto_processor(): void
    {
        $processor = PdfProcessorFactory::make('auto');

        $this->assertInstanceOf(AutoPdfProcessor::class, $processor);
        $this->assertEquals('auto', $processor->getName());
    }

    public function test_factory_throws_for_unknown_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown PDF processor driver/');

        PdfProcessorFactory::make('nonexistent');
    }

    public function test_factory_lists_available_drivers(): void
    {
        $drivers = PdfProcessorFactory::availableDrivers();

        $this->assertContains('native', $drivers);
        $this->assertContains('ocr', $drivers);
        $this->assertContains('auto', $drivers);
    }

    public function test_factory_from_config_uses_config_driver(): void
    {
        config(['ai.pdf_processor.driver' => 'native']);

        $processor = PdfProcessorFactory::fromConfig();

        $this->assertInstanceOf(NativePdfProcessor::class, $processor);
    }

    // ─── IoC Container ───────────────────────────────────────────────────────

    public function test_container_resolves_pdf_processor_interface(): void
    {
        $processor = app(PdfProcessorInterface::class);

        $this->assertInstanceOf(PdfProcessorInterface::class, $processor);
    }

    // ─── PdfMetadata Value Object ────────────────────────────────────────────

    public function test_pdf_metadata_file_size_mb(): void
    {
        $metadata = new PdfMetadata(
            filePath:        '/tmp/test.pdf',
            pageCount:       5,
            fileSizeBytes:   2 * 1024 * 1024,
            isEncrypted:     false,
            isLikelyScanned: false,
        );

        $this->assertEquals(2.0, $metadata->fileSizeMb());
    }

    public function test_pdf_metadata_to_array_contains_required_keys(): void
    {
        $metadata = new PdfMetadata(
            filePath:        '/tmp/test.pdf',
            pageCount:       3,
            fileSizeBytes:   1024,
            isEncrypted:     false,
            isLikelyScanned: true,
            title:           'Test PDF',
        );

        $array = $metadata->toArray();

        $this->assertArrayHasKey('page_count', $array);
        $this->assertArrayHasKey('file_size_bytes', $array);
        $this->assertArrayHasKey('is_encrypted', $array);
        $this->assertArrayHasKey('is_likely_scanned', $array);
        $this->assertEquals(3, $array['page_count']);
        $this->assertTrue($array['is_likely_scanned']);
    }

    // ─── PdfProcessingResult Value Object ────────────────────────────────────

    public function test_processing_result_is_empty_for_blank_text(): void
    {
        $result = $this->makeResult('   ');

        $this->assertTrue($result->isEmpty());
    }

    public function test_processing_result_is_not_empty_for_real_text(): void
    {
        $result = $this->makeResult('Factura nr. 123');

        $this->assertFalse($result->isEmpty());
    }

    public function test_processing_result_char_count(): void
    {
        $result = $this->makeResult('Hello World');

        $this->assertEquals(11, $result->charCount());
    }

    public function test_processing_result_truncate_returns_same_when_under_limit(): void
    {
        $result    = $this->makeResult('Short text');
        $truncated = $result->truncate(1000);

        $this->assertEquals('Short text', $truncated->text);
    }

    public function test_processing_result_truncate_cuts_at_max_chars(): void
    {
        $result    = $this->makeResult('ABCDEFGHIJ');
        $truncated = $result->truncate(5);

        $this->assertEquals('ABCDE', $truncated->text);
        $this->assertEquals(5, $truncated->charCount());
    }

    public function test_processing_result_truncate_preserves_metadata(): void
    {
        $result    = $this->makeResult('Long text here');
        $truncated = $result->truncate(4);

        $this->assertSame($result->metadata, $truncated->metadata);
        $this->assertSame($result->processorUsed, $truncated->processorUsed);
    }

    public function test_processing_result_to_array_contains_required_keys(): void
    {
        $result = $this->makeResult('Some text', 'native', 123.4);
        $array  = $result->toArray();

        $this->assertArrayHasKey('char_count', $array);
        $this->assertArrayHasKey('processor_used', $array);
        $this->assertArrayHasKey('processing_time_ms', $array);
        $this->assertArrayHasKey('fell_back_to_ocr', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('native', $array['processor_used']);
        $this->assertEquals(123.4, $array['processing_time_ms']);
    }

    // ─── NativePdfProcessor availability ─────────────────────────────────────

    public function test_native_processor_is_available_when_pdftotext_exists(): void
    {
        // pdftotext e instalat în container
        $processor = PdfProcessorFactory::make('native');

        $this->assertTrue($processor->isAvailable());
    }

    public function test_native_processor_throws_for_missing_file(): void
    {
        $this->expectException(PdfNotReadableException::class);

        $processor = PdfProcessorFactory::make('native');
        $processor->extract('/tmp/does_not_exist_12345.pdf');
    }

    // ─── OcrPdfProcessor availability ────────────────────────────────────────

    public function test_ocr_processor_is_not_available_when_tesseract_missing(): void
    {
        config(['ai.pdf_processor.tesseract_path' => '/usr/bin/nonexistent_tesseract']);

        $processor = PdfProcessorFactory::make('ocr');

        $this->assertFalse($processor->isAvailable());
    }

    public function test_ocr_processor_throws_ocr_not_available_exception(): void
    {
        $this->expectException(OcrNotAvailableException::class);

        config(['ai.pdf_processor.tesseract_path' => '/usr/bin/nonexistent_tesseract']);

        $processor = PdfProcessorFactory::make('ocr');
        $processor->extract('/tmp/any.pdf');
    }

    // ─── Exceptions ──────────────────────────────────────────────────────────

    public function test_pdf_not_readable_exception_file_not_found_message(): void
    {
        $e = PdfNotReadableException::fileNotFound('/tmp/missing.pdf');

        $this->assertStringContainsString('/tmp/missing.pdf', $e->getMessage());
        $this->assertStringContainsString('not found', $e->getMessage());
    }

    public function test_pdf_not_readable_exception_encrypted_message(): void
    {
        $e = PdfNotReadableException::encrypted('/tmp/secret.pdf');

        $this->assertStringContainsString('encrypted', $e->getMessage());
    }

    public function test_ocr_not_available_exception_tesseract_missing_message(): void
    {
        $e = OcrNotAvailableException::tesseractMissing('/usr/bin/tesseract');

        $this->assertStringContainsString('Tesseract', $e->getMessage());
        $this->assertStringContainsString('/usr/bin/tesseract', $e->getMessage());
    }

    public function test_pdf_processing_exception_from_command(): void
    {
        $e = PdfProcessingException::fromCommand('pdftotext file.pdf', 1, 'Error output');

        $this->assertStringContainsString('exit 1', $e->getMessage());
        $this->assertStringContainsString('Error output', $e->getMessage());
    }

    // ─── Config ──────────────────────────────────────────────────────────────

    public function test_config_has_pdf_processor_section(): void
    {
        $this->assertIsArray(config('ai.pdf_processor'));
    }

    public function test_config_pdf_processor_has_required_keys(): void
    {
        $cfg = config('ai.pdf_processor');

        $this->assertArrayHasKey('driver', $cfg);
        $this->assertArrayHasKey('pdftotext_path', $cfg);
        $this->assertArrayHasKey('pdfinfo_path', $cfg);
        $this->assertArrayHasKey('tesseract_path', $cfg);
        $this->assertArrayHasKey('min_chars_per_page', $cfg);
    }

    public function test_config_default_driver_is_auto(): void
    {
        $this->assertEquals('auto', config('ai.pdf_processor.driver'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeResult(
        string $text,
        string $processor = 'native',
        float  $ms = 10.0
    ): PdfProcessingResult {
        $metadata = new PdfMetadata(
            filePath:        '/tmp/test.pdf',
            pageCount:       1,
            fileSizeBytes:   1024,
            isEncrypted:     false,
            isLikelyScanned: false,
        );

        return new PdfProcessingResult(
            text:             $text,
            metadata:         $metadata,
            processorUsed:    $processor,
            processingTimeMs: $ms,
        );
    }
}
