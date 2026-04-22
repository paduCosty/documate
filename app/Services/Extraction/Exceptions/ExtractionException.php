<?php

namespace App\Services\Extraction\Exceptions;

use RuntimeException;

/**
 * Base exception for all errors in the Extraction Engine.
 *
 * Hierarchy:
 *   ExtractionException
 *   ├── TemplateNotFoundException    — template slug not found in DB
 *   └── JsonExtractionFailedException — AI never returned valid JSON after all retries
 */
class ExtractionException extends RuntimeException {}
