<?php

namespace App\Services\Conversion;

class OfficeToPdfConfig
{
    public function __construct(
        public readonly string $type,
        public readonly string $operationType,
        public readonly array  $mimes,
        public readonly string $converter,
        public readonly string $outputPrefix,
        public readonly int    $maxFiles = 10,
    ) {}
}
