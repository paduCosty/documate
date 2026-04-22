<?php

namespace App\Services\Pdf\ValueObjects;

/**
 * Rezultatul imutabil al extragerii de text dintr-un PDF.
 * Pasat mai departe către AI Provider Layer (Faza 4).
 */
final class PdfProcessingResult
{
    public function __construct(
        public readonly string      $text,
        public readonly PdfMetadata $metadata,
        public readonly string      $processorUsed,
        public readonly float       $processingTimeMs,
        public readonly bool        $fellBackToOcr = false,
    ) {}

    public function isEmpty(): bool
    {
        return trim($this->text) === '';
    }

    public function charCount(): int
    {
        return mb_strlen($this->text);
    }

    public function truncate(int $maxChars): static
    {
        if ($this->charCount() <= $maxChars) {
            return $this;
        }

        return new static(
            text:             mb_substr($this->text, 0, $maxChars),
            metadata:         $this->metadata,
            processorUsed:    $this->processorUsed,
            processingTimeMs: $this->processingTimeMs,
            fellBackToOcr:    $this->fellBackToOcr,
        );
    }

    public function toArray(): array
    {
        return [
            'char_count'         => $this->charCount(),
            'processor_used'     => $this->processorUsed,
            'processing_time_ms' => $this->processingTimeMs,
            'fell_back_to_ocr'   => $this->fellBackToOcr,
            'metadata'           => $this->metadata->toArray(),
        ];
    }
}
