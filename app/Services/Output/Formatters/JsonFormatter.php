<?php

namespace App\Services\Output\Formatters;

use App\Services\Extraction\ValueObjects\ExtractionResult;
use App\Services\Output\Exceptions\FormatterException;
use App\Services\Output\ValueObjects\FormattedOutput;

/**
 * Produces a pretty-printed .json file from an ExtractionResult.
 *
 * The output contains two top-level keys:
 *   "data"     — the normalized extracted data (the main payload)
 *   "metadata" — template, provider, model, token counts, timing
 *
 * This envelope makes the file self-describing and useful for downstream
 * integrations that need to know how and when the data was extracted.
 */
class JsonFormatter extends AbstractFormatter
{
    public function getFormat(): string    { return 'json'; }
    public function getMimeType(): string  { return 'application/json'; }
    public function getExtension(): string { return 'json'; }

    public function format(ExtractionResult $result, string $outputDir): FormattedOutput
    {
        $this->ensureDirectory($outputDir);

        $filename = $this->generateFilename($result);
        $path     = $outputDir . '/' . $filename;

        $payload = [
            'data'     => $result->data,
            'metadata' => [
                'template'           => $result->templateSlug,
                'provider'           => $result->aiMetadata['provider_used'] ?? null,
                'model'              => $result->aiMetadata['model_used'] ?? null,
                'input_tokens'       => $result->aiMetadata['input_tokens'] ?? null,
                'output_tokens'      => $result->aiMetadata['output_tokens'] ?? null,
                'total_ms'           => $result->totalMs(),
                'used_fallback'      => $result->usedFallbackProvider(),
                'validation_warnings'=> $result->validationWarnings,
                'extracted_at'       => now()->toIso8601String(),
            ],
        ];

        try {
            $json = json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw FormatterException::writeFailed('json', $path, $e);
        }

        $this->writeContent($path, $json);

        return new FormattedOutput(
            absolutePath:  $path,
            filename:      $filename,
            mimeType:      $this->getMimeType(),
            format:        $this->getFormat(),
            fileSizeBytes: filesize($path),
        );
    }
}
