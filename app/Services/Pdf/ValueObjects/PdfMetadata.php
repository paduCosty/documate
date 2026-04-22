<?php

namespace App\Services\Pdf\ValueObjects;

/**
 * Metadata imutabilă despre un fișier PDF, extrasă înainte de procesare.
 * Folosit de AutoPdfProcessor pentru a decide ce strategie să aplice.
 */
final class PdfMetadata
{
    public function __construct(
        public readonly string  $filePath,
        public readonly int     $pageCount,
        public readonly int     $fileSizeBytes,
        public readonly bool    $isEncrypted,
        public readonly bool    $isLikelyScanned,
        public readonly ?string $title   = null,
        public readonly ?string $author  = null,
        public readonly ?string $creator = null,
    ) {}

    public function fileSizeMb(): float
    {
        return round($this->fileSizeBytes / 1024 / 1024, 2);
    }

    public function toArray(): array
    {
        return [
            'page_count'        => $this->pageCount,
            'file_size_bytes'   => $this->fileSizeBytes,
            'is_encrypted'      => $this->isEncrypted,
            'is_likely_scanned' => $this->isLikelyScanned,
            'title'             => $this->title,
            'author'            => $this->author,
            'creator'           => $this->creator,
        ];
    }
}
