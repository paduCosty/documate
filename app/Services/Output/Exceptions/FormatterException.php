<?php

namespace App\Services\Output\Exceptions;

use RuntimeException;

/**
 * Thrown when a formatter fails to produce the output file.
 */
class FormatterException extends RuntimeException
{
    public static function writeFailed(string $format, string $path, \Throwable $cause): static
    {
        return new static(
            "Failed to write {$format} output to \"{$path}\": {$cause->getMessage()}",
            0,
            $cause,
        );
    }

    public static function directoryNotWritable(string $path): static
    {
        return new static("Output directory is not writable: {$path}");
    }
}
