<?php

namespace Tests\Unit\Extraction;

use App\Models\ExtractionTemplate;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Ai\ValueObjects\AiResponse;
use App\Services\Extraction\ExtractionService;
use App\Services\Extraction\Exceptions\JsonExtractionFailedException;
use App\Services\Extraction\Exceptions\TemplateNotFoundException;
use App\Services\Extraction\JsonExtractor;
use App\Services\Extraction\JsonValidator;
use App\Services\Extraction\PromptBuilder;
use App\Services\Extraction\ResultNormalizer;
use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;
use Tests\TestCase;

/**
 * Unit tests for the Extraction Engine (Phase 5).
 * Run with: php artisan test --filter=ExtractionEngineTest
 */
class ExtractionEngineTest extends TestCase
{
    // ─── JsonExtractor ────────────────────────────────────────────────────────

    public function test_extractor_returns_plain_json_object(): void
    {
        $e = new JsonExtractor();
        $this->assertEquals('{"key":"value"}', $e->extract('{"key":"value"}'));
    }

    public function test_extractor_strips_json_code_fence(): void
    {
        $raw = "```json\n{\"key\":\"value\"}\n```";
        $this->assertEquals('{"key":"value"}', (new JsonExtractor())->extract($raw));
    }

    public function test_extractor_strips_generic_code_fence(): void
    {
        $raw = "```\n{\"key\":\"value\"}\n```";
        $this->assertEquals('{"key":"value"}', (new JsonExtractor())->extract($raw));
    }

    public function test_extractor_finds_json_in_surrounding_text(): void
    {
        $raw = 'Here is the result: {"invoice":"123"} Hope that helps!';
        $result = (new JsonExtractor())->extract($raw);
        $this->assertEquals('{"invoice":"123"}', $result);
    }

    public function test_extractor_handles_nested_json(): void
    {
        $raw = '{"a":{"b":{"c":1}}}';
        $this->assertEquals($raw, (new JsonExtractor())->extract($raw));
    }

    public function test_extractor_handles_json_array(): void
    {
        $raw = '[{"id":1},{"id":2}]';
        $this->assertEquals($raw, (new JsonExtractor())->extract($raw));
    }

    public function test_extractor_returns_null_for_no_json(): void
    {
        $this->assertNull((new JsonExtractor())->extract('Just some plain text with no JSON.'));
    }

    public function test_extractor_ignores_fence_with_non_json_content(): void
    {
        $raw = "```python\nprint('hello')\n```";
        // No JSON object/array, extractor must not return Python code.
        $result = (new JsonExtractor())->extract($raw);
        $this->assertNull($result);
    }

    // ─── JsonValidator ────────────────────────────────────────────────────────

    public function test_validator_parses_valid_json(): void
    {
        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];
        $result = $this->makeValidator()->validate('{"name":"Acme"}', $schema);

        $this->assertEquals(['name' => 'Acme'], $result['data']);
        $this->assertEmpty($result['warnings']);
    }

    public function test_validator_throws_json_exception_when_no_json_found(): void
    {
        $this->expectException(\JsonException::class);

        $this->makeValidator()->validate('No JSON here at all.', []);
    }

    public function test_validator_returns_warnings_for_schema_violations(): void
    {
        $schema = [
            'type'       => 'object',
            'properties' => ['amount' => ['type' => 'number']],
            'required'   => ['amount'],
        ];

        // "amount" is a string, violates schema — but data is still returned.
        $result = $this->makeValidator()->validate('{"amount":"not-a-number"}', $schema);

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_validator_accepts_json_inside_markdown_fence(): void
    {
        $schema = ['type' => 'object'];
        $raw    = "```json\n{\"total\": 100}\n```";

        $result = $this->makeValidator()->validate($raw, $schema);
        $this->assertEquals(100, $result['data']['total']);
    }

    public function test_validator_returns_empty_warnings_for_valid_schema(): void
    {
        $schema = [
            'type'       => 'object',
            'properties' => [
                'number' => ['type' => 'string'],
                'total'  => ['type' => 'number'],
            ],
        ];

        $result = $this->makeValidator()->validate('{"number":"INV-001","total":99.5}', $schema);

        $this->assertEmpty($result['warnings']);
    }

    // ─── ResultNormalizer ────────────────────────────────────────────────────

    public function test_normalizer_trims_strings(): void
    {
        $result = (new ResultNormalizer())->normalize(['name' => '  Acme  ']);
        $this->assertEquals('Acme', $result['name']);
    }

    public function test_normalizer_converts_null_string_to_null(): void
    {
        $result = (new ResultNormalizer())->normalize(['field' => 'null']);
        $this->assertNull($result['field']);
    }

    public function test_normalizer_converts_na_string_to_null(): void
    {
        $result = (new ResultNormalizer())->normalize(['field' => 'n/a']);
        $this->assertNull($result['field']);
    }

    public function test_normalizer_converts_dash_to_null(): void
    {
        $result = (new ResultNormalizer())->normalize(['field' => '-']);
        $this->assertNull($result['field']);
    }

    public function test_normalizer_converts_european_date_to_iso(): void
    {
        $result = (new ResultNormalizer())->normalize(['date' => '15.03.2024']);
        $this->assertEquals('2024-03-15', $result['date']);
    }

    public function test_normalizer_converts_slash_date_to_iso(): void
    {
        $result = (new ResultNormalizer())->normalize(['date' => '15/03/2024']);
        $this->assertEquals('2024-03-15', $result['date']);
    }

    public function test_normalizer_leaves_iso_date_unchanged(): void
    {
        $result = (new ResultNormalizer())->normalize(['date' => '2024-03-15']);
        $this->assertEquals('2024-03-15', $result['date']);
    }

    public function test_normalizer_parses_european_number(): void
    {
        $result = (new ResultNormalizer())->normalize(['amount' => '1.234,56 RON']);
        $this->assertEquals(1234.56, $result['amount']);
    }

    public function test_normalizer_parses_us_number(): void
    {
        $result = (new ResultNormalizer())->normalize(['amount' => '1,234.56']);
        $this->assertEquals(1234.56, $result['amount']);
    }

    public function test_normalizer_strips_currency_symbol(): void
    {
        $result = (new ResultNormalizer())->normalize(['price' => '€99.50']);
        $this->assertEquals(99.50, $result['price']);
    }

    public function test_normalizer_passes_through_integer(): void
    {
        $result = (new ResultNormalizer())->normalize(['count' => 5]);
        $this->assertEquals(5, $result['count']);
    }

    public function test_normalizer_passes_through_null(): void
    {
        $result = (new ResultNormalizer())->normalize(['field' => null]);
        $this->assertNull($result['field']);
    }

    public function test_normalizer_works_recursively_on_nested_arrays(): void
    {
        $input = [
            'vendor' => [
                'name'    => '  Acme Corp  ',
                'vat_id'  => 'null',
                'founded' => '01.01.2000',
            ],
        ];

        $result = (new ResultNormalizer())->normalize($input);

        $this->assertEquals('Acme Corp', $result['vendor']['name']);
        $this->assertNull($result['vendor']['vat_id']);
        $this->assertEquals('2000-01-01', $result['vendor']['founded']);
    }

    public function test_normalizer_preserves_booleans(): void
    {
        $result = (new ResultNormalizer())->normalize(['active' => true, 'deleted' => false]);
        $this->assertTrue($result['active']);
        $this->assertFalse($result['deleted']);
    }

    // ─── PromptBuilder ────────────────────────────────────────────────────────

    public function test_prompt_builder_replaces_placeholders(): void
    {
        $template = $this->makeTemplate('Extract: {pdf_text}. Schema: {output_schema}');
        $pdf      = $this->makePdfResult('Invoice text here');

        $request = (new PromptBuilder())->build($template, $pdf);

        $this->assertStringContainsString('Invoice text here', $request->prompt);
        $this->assertStringNotContainsString('{pdf_text}', $request->prompt);
        $this->assertStringNotContainsString('{output_schema}', $request->prompt);
    }

    public function test_prompt_builder_uses_low_temperature(): void
    {
        $template = $this->makeTemplate('{pdf_text} {output_schema}');
        $pdf      = $this->makePdfResult('text');

        $request = (new PromptBuilder())->build($template, $pdf);

        $this->assertLessThanOrEqual(0.2, $request->temperature);
    }

    public function test_prompt_builder_truncates_long_text(): void
    {
        config(['ai.extraction.text_max_chars' => 50]);

        $longText = str_repeat('word ', 100); // 500 chars
        $template = $this->makeTemplate('{pdf_text} {output_schema}');
        $pdf      = $this->makePdfResult($longText);

        $request = (new PromptBuilder())->build($template, $pdf);

        $this->assertStringContainsString('[... text truncated ...]', $request->prompt);
    }

    public function test_prompt_builder_does_not_truncate_short_text(): void
    {
        config(['ai.extraction.text_max_chars' => 8000]);

        $template = $this->makeTemplate('{pdf_text} {output_schema}');
        $pdf      = $this->makePdfResult('Short text');

        $request = (new PromptBuilder())->build($template, $pdf);

        $this->assertStringNotContainsString('[... text truncated ...]', $request->prompt);
    }

    public function test_prompt_builder_correction_prompt_contains_previous_response(): void
    {
        $template = $this->makeTemplate('{pdf_text} {output_schema}');
        $pdf      = $this->makePdfResult('text');

        $request = (new PromptBuilder())->buildCorrectionPrompt(
            $template, $pdf, 'invalid response here', 1
        );

        $this->assertStringContainsString('invalid response here', $request->prompt);
        $this->assertStringContainsString('attempt #1', $request->prompt);
        $this->assertEquals(0.0, $request->temperature);
    }

    // ─── ExtractionResult Value Object ───────────────────────────────────────

    public function test_extraction_result_total_ms(): void
    {
        $result = $this->makeExtractionResult(pdfMs: 100.0, aiMs: 250.0);
        $this->assertEquals(350.0, $result->totalMs());
    }

    public function test_extraction_result_has_warnings(): void
    {
        $result = $this->makeExtractionResult(warnings: ['[amount] wrong type']);
        $this->assertTrue($result->hasWarnings());
    }

    public function test_extraction_result_has_no_warnings_by_default(): void
    {
        $result = $this->makeExtractionResult();
        $this->assertFalse($result->hasWarnings());
    }

    public function test_extraction_result_to_array_has_required_keys(): void
    {
        $array = $this->makeExtractionResult()->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('template_slug', $array);
        $this->assertArrayHasKey('pdf_metadata', $array);
        $this->assertArrayHasKey('ai_metadata', $array);
        $this->assertArrayHasKey('total_ms', $array);
        $this->assertArrayHasKey('validation_warnings', $array);
    }

    // ─── ExtractionService ────────────────────────────────────────────────────

    public function test_service_is_bound_in_container(): void
    {
        $service = app(ExtractionService::class);
        $this->assertInstanceOf(ExtractionService::class, $service);
    }

    public function test_service_throws_template_not_found_for_unknown_slug(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessageMatches('/non_existent_template/');

        $service = $this->makeService(aiResponse: $this->makeAiResponse('{}'));
        $service->extract('/tmp/fake.pdf', 'non_existent_template');
    }

    public function test_service_returns_extraction_result_on_success(): void
    {
        $this->seedTemplates();

        $aiResponse = $this->makeAiResponse('{"invoice_number":"INV-001"}');
        $service    = $this->makeService(aiResponse: $aiResponse);

        $result = $service->extract('/tmp/fake.pdf', 'invoice');

        $this->assertInstanceOf(ExtractionResult::class, $result);
        $this->assertEquals('invoice', $result->templateSlug);
        $this->assertArrayHasKey('invoice_number', $result->data);
    }

    public function test_service_throws_json_extraction_failed_after_all_retries(): void
    {
        $this->seedTemplates();
        config(['ai.extraction.json_retries' => 2]);

        $this->expectException(JsonExtractionFailedException::class);

        // AI always returns garbage text.
        $service = $this->makeService(aiResponse: $this->makeAiResponse('not json at all'));
        $service->extract('/tmp/fake.pdf', 'invoice');
    }

    public function test_service_retries_on_invalid_json_then_succeeds(): void
    {
        $this->seedTemplates();
        config(['ai.extraction.json_retries' => 3]);

        $callCount = 0;
        $ai        = $this->createMock(AiProviderInterface::class);
        $ai->method('complete')->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            // Fail first, succeed on second attempt.
            $text = $callCount === 1 ? 'bad response' : '{"invoice_number":"INV-001"}';
            return $this->makeAiResponse($text);
        });
        $ai->method('getName')->willReturn('ollama');

        $service = $this->makeService(aiProvider: $ai);
        $result  = $service->extract('/tmp/fake.pdf', 'invoice');

        $this->assertEquals(2, $callCount);
        $this->assertInstanceOf(ExtractionResult::class, $result);
    }

    public function test_service_normalizes_extracted_data(): void
    {
        $this->seedTemplates();

        // AI returns a date in European format — should be normalized to ISO.
        $service = $this->makeService(
            aiResponse: $this->makeAiResponse('{"invoice_date":"15.03.2024"}')
        );

        $result = $service->extract('/tmp/fake.pdf', 'invoice');

        $this->assertEquals('2024-03-15', $result->data['invoice_date']);
    }

    public function test_exception_carries_attempt_count_and_last_raw_response(): void
    {
        $this->seedTemplates();
        config(['ai.extraction.json_retries' => 2]);

        $service = $this->makeService(aiResponse: $this->makeAiResponse('not json'));

        try {
            $service->extract('/tmp/fake.pdf', 'invoice');
            $this->fail('Expected JsonExtractionFailedException');
        } catch (JsonExtractionFailedException $e) {
            $this->assertEquals(2, $e->getAttempts());
            $this->assertEquals('not json', $e->getLastRawResponse());
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeValidator(): JsonValidator
    {
        return new JsonValidator(new JsonExtractor());
    }

    private function makeTemplate(string $promptTemplate = '{pdf_text} {output_schema}'): ExtractionTemplate
    {
        $t                  = new ExtractionTemplate();
        $t->slug            = 'test';
        $t->name            = 'Test';
        $t->prompt_template = $promptTemplate;
        $t->output_schema   = ['type' => 'object'];
        $t->is_system       = true;
        $t->active          = true;

        return $t;
    }

    private function makePdfResult(string $text = 'Sample PDF text'): PdfProcessingResult
    {
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
            processorUsed:    'native',
            processingTimeMs: 50.0,
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

    private function makeExtractionResult(
        array $data    = ['key' => 'value'],
        float $pdfMs   = 50.0,
        float $aiMs    = 200.0,
        array $warnings = [],
    ): ExtractionResult {
        $metadata = new PdfMetadata('/tmp/t.pdf', 1, 1024, false, false);

        return new ExtractionResult(
            data:               $data,
            templateSlug:       'invoice',
            pdfMetadata:        $metadata,
            aiMetadata:         ['provider_used' => 'ollama', 'used_fallback' => false],
            pdfProcessingMs:    $pdfMs,
            aiProcessingMs:     $aiMs,
            validationWarnings: $warnings,
        );
    }

    private function makeService(
        ?AiResponse          $aiResponse = null,
        ?AiProviderInterface $aiProvider = null,
    ): ExtractionService {
        $pdfMock = $this->createMock(PdfProcessorInterface::class);
        $pdfMock->method('extract')->willReturn($this->makePdfResult());

        if ($aiProvider === null) {
            $aiProvider = $this->createMock(AiProviderInterface::class);
            $aiProvider->method('complete')->willReturn(
                $aiResponse ?? $this->makeAiResponse('{}')
            );
            $aiProvider->method('getName')->willReturn('ollama');
        }

        return new ExtractionService(
            pdfProcessor:  $pdfMock,
            aiProvider:    $aiProvider,
            promptBuilder: new PromptBuilder(),
            jsonValidator: new JsonValidator(new JsonExtractor()),
            normalizer:    new ResultNormalizer(),
        );
    }

    private function seedTemplates(): void
    {
        \Illuminate\Support\Facades\DB::table('extraction_templates')->updateOrInsert(
            ['slug' => 'invoice', 'user_id' => null],
            [
                'name'             => 'Invoice',
                'prompt_template'  => 'Extract from: {pdf_text}. Schema: {output_schema}',
                'output_schema'    => json_encode(['type' => 'object']),
                'is_system'        => true,
                'active'           => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );
    }
}
