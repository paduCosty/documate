<?php

namespace App\Services\Extraction\Exceptions;

class TemplateNotFoundException extends ExtractionException
{
    public static function forSlug(string $slug): static
    {
        return new static("Extraction template \"{$slug}\" not found or is inactive.");
    }
}
