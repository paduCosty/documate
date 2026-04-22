<?php

namespace App\Services\Extraction\Exceptions;

class JsonExtractionFailedException extends ExtractionException
{
    public function __construct(
        string $message,
        private readonly int    $attempts,
        private readonly string $lastRawResponse,
    ) {
        parent::__construct($message);
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * The last raw text the AI returned before all retries were exhausted.
     * Useful for debugging and logging.
     */
    public function getLastRawResponse(): string
    {
        return $this->lastRawResponse;
    }

    public static function afterRetries(int $attempts, string $lastRaw): static
    {
        return new static(
            "Failed to extract valid JSON after {$attempts} attempt(s).",
            $attempts,
            $lastRaw,
        );
    }
}
