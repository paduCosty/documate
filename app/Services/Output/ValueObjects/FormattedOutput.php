<?php

namespace App\Services\Output\ValueObjects;

/**
 * Immutable descriptor of a formatted output file written to disk.
 * Passed to the controller to build the download response.
 */
final class FormattedOutput
{
    public function __construct(
        public readonly string $absolutePath,
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly string $format,
        public readonly int    $fileSizeBytes,
    ) {}

    public function fileSizeMb(): float
    {
        return round($this->fileSizeBytes / 1024 / 1024, 2);
    }

    public function toArray(): array
    {
        return [
            'filename'        => $this->filename,
            'mime_type'       => $this->mimeType,
            'format'          => $this->format,
            'file_size_bytes' => $this->fileSizeBytes,
        ];
    }
}
