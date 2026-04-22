<?php

namespace App\Services\Extraction\ValueObjects;

use App\Services\Pdf\ValueObjects\PdfMetadata;

/**
 * Immutable result of a complete extraction pipeline run.
 *
 * Carries everything needed for: displaying results, writing to DB,
 * generating output files, and building audit logs.
 */
final class ExtractionResult
{
    public function __construct(
        /** Normalized, validated data extracted from the PDF. */
        public readonly array       $data,

        /** Slug of the template used. */
        public readonly string      $templateSlug,

        /** Metadata about the source PDF file. */
        public readonly PdfMetadata $pdfMetadata,

        /** Serialized AiResponse metadata (provider, model, tokens, latency). */
        public readonly array       $aiMetadata,

        /** How long PDF text extraction took, in milliseconds. */
        public readonly float       $pdfProcessingMs,

        /** How long the AI call(s) took in total, in milliseconds. */
        public readonly float       $aiProcessingMs,

        /** Non-fatal schema validation warnings (data was returned despite them). */
        public readonly array       $validationWarnings = [],
    ) {}

    public function totalMs(): float
    {
        return $this->pdfProcessingMs + $this->aiProcessingMs;
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->validationWarnings);
    }

    public function usedFallbackProvider(): bool
    {
        return (bool) ($this->aiMetadata['used_fallback'] ?? false);
    }

    public function toArray(): array
    {
        return [
            'data'               => $this->data,
            'template_slug'      => $this->templateSlug,
            'pdf_metadata'       => $this->pdfMetadata->toArray(),
            'ai_metadata'        => $this->aiMetadata,
            'pdf_processing_ms'  => $this->pdfProcessingMs,
            'ai_processing_ms'   => $this->aiProcessingMs,
            'total_ms'           => $this->totalMs(),
            'validation_warnings'=> $this->validationWarnings,
        ];
    }
}
