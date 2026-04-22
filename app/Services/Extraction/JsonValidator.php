<?php

namespace App\Services\Extraction;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * Validates a JSON string against the template's output schema.
 *
 * Validation is intentionally lenient:
 *   - If the JSON is syntactically valid but violates the schema,
 *     the data is still returned along with a list of warnings.
 *   - Only syntactic JSON errors (unparseable string) cause a hard failure.
 *
 * This design avoids discarding useful data because the AI returned
 * a slightly wrong type (e.g. "123" instead of 123).
 */
class JsonValidator
{
    public function __construct(
        private readonly JsonExtractor $extractor,
    ) {}

    /**
     * Parses and validates raw AI output against a JSON Schema.
     *
     * @param  string $raw        Raw text from the AI (may contain markdown, prose, etc.)
     * @param  array  $schema     The JSON Schema from the ExtractionTemplate.
     * @return array{data: array, warnings: string[]}
     *
     * @throws \JsonException       If no valid JSON structure can be found in $raw.
     */
    public function validate(string $raw, array $schema): array
    {
        $jsonString = $this->extractor->extract($raw);

        if ($jsonString === null) {
            throw new \JsonException("No JSON structure found in AI response.");
        }

        $data = json_decode($jsonString, associative: true, flags: JSON_THROW_ON_ERROR);

        $warnings = $this->runSchemaValidation($data, $schema);

        return [
            'data'     => $data,
            'warnings' => $warnings,
        ];
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * Runs the JSON Schema validation and collects constraint violations as warnings.
     * Returns an empty array when the data fully complies with the schema.
     *
     * @return string[]
     */
    private function runSchemaValidation(array $data, array $schema): array
    {
        $validator   = new Validator();
        $dataObject  = json_decode(json_encode($data)); // Convert to stdClass tree.
        $schemaObject = json_decode(json_encode($schema));

        $validator->validate(
            $dataObject,
            $schemaObject,
            Constraint::CHECK_MODE_COERCE_TYPES,  // Auto-coerce "123" → 123 etc.
        );

        if ($validator->isValid()) {
            return [];
        }

        return array_map(
            fn (array $error) => sprintf('[%s] %s', $error['property'] ?: 'root', $error['message']),
            $validator->getErrors(),
        );
    }
}
