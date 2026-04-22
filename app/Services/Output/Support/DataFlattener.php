<?php

namespace App\Services\Output\Support;

/**
 * Converts a nested ExtractionResult::$data array into a flat, spreadsheet-friendly
 * representation composed of two parts:
 *
 *   scalars     — key/value pairs (dot-notation for nested objects):
 *                 ['invoice_number' => 'INV-001', 'vendor.name' => 'Acme', ...]
 *
 *   collections — named 2D tables built from arrays-of-objects or the special
 *                 "tables" structure used by the TableExtractor template:
 *                 ['line_items' => [['description','qty','total'], ['Item 1', 2, 100]]]
 *
 * Decision rules (applied per top-level key):
 *   1. Scalar (string/int/float/bool/null)  → scalars
 *   2. Indexed array of objects             → collections (table name = key)
 *   3. Special "tables" key with title/headers/rows schema → collections (one per table)
 *   4. Associative array (nested object)    → scalars with dot-notation prefix
 *   5. Indexed array of scalars             → scalar with comma-joined value
 */
class DataFlattener
{
    /**
     * @return array{scalars: array<string, mixed>, collections: array<string, list<list<mixed>>>}
     */
    public function flatten(array $data): array
    {
        $scalars     = [];
        $collections = [];

        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                $scalars[(string) $key] = $value;
                continue;
            }

            // Special case: "tables" key from the TableExtractor template.
            if ($key === 'tables' && $this->isTablesStructure($value)) {
                foreach ($value as $i => $table) {
                    $title               = (string) ($table['title'] ?? ('Table ' . ($i + 1)));
                    $collections[$title] = $this->tableStructureToRows($table);
                }
                continue;
            }

            if ($this->isIndexedArrayOfObjects($value)) {
                $collections[(string) $key] = $this->arrayOfObjectsToRows($value);
                continue;
            }

            if ($this->isAssocArray($value)) {
                $this->flattenAssoc($value, (string) $key, $scalars);
                continue;
            }

            // Indexed array of scalars → join as string.
            $scalars[(string) $key] = implode(', ', array_map(
                fn ($v) => $v === null ? '' : (string) $v,
                $value,
            ));
        }

        return [
            'scalars'     => $scalars,
            'collections' => $collections,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Converts an array-of-objects into a 2D table:
     *   Row 0: merged headers (union of all keys across all objects)
     *   Row 1+: values, aligned to the header order
     */
    private function arrayOfObjectsToRows(array $items): array
    {
        // Collect the union of all keys to build a complete header row.
        $headers = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                foreach (array_keys($item) as $k) {
                    $headers[$k] = true;
                }
            }
        }
        $headers = array_keys($headers);

        $rows = [$headers];

        foreach ($items as $item) {
            if (! is_array($item)) {
                $rows[] = array_fill(0, count($headers), (string) $item);
                continue;
            }

            $row = [];
            foreach ($headers as $h) {
                $cell = $item[$h] ?? null;
                // Flatten nested objects inside a collection row to a JSON string.
                $row[] = is_array($cell) ? json_encode($cell, JSON_UNESCAPED_UNICODE) : $cell;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Converts a {"title": ..., "headers": [...], "rows": [[...]]} structure
     * (as produced by the TableExtractor template) to a 2D array.
     */
    private function tableStructureToRows(array $table): array
    {
        $rows = [];

        if (! empty($table['headers'])) {
            $rows[] = array_values($table['headers']);
        }

        foreach ($table['rows'] ?? [] as $row) {
            $rows[] = is_array($row) ? array_values($row) : [(string) $row];
        }

        return $rows;
    }

    /**
     * Recursively flattens a nested associative array into dot-notation scalar entries.
     * Stops recursing when it hits an array-of-objects (stored as JSON).
     */
    private function flattenAssoc(array $data, string $prefix, array &$scalars): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix . '.' . $key;

            if (! is_array($value)) {
                $scalars[$fullKey] = $value;
                continue;
            }

            if ($this->isAssocArray($value)) {
                $this->flattenAssoc($value, $fullKey, $scalars);
                continue;
            }

            // Array of scalars or objects: encode as JSON string in the scalar table.
            $scalars[$fullKey] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Returns true if $value is a non-empty list of associative arrays (objects).
     */
    private function isIndexedArrayOfObjects(array $value): bool
    {
        if (empty($value) || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item) && $this->isAssocArray($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if $value matches the tables-template structure:
     * a list of objects each having at least a "rows" key.
     */
    private function isTablesStructure(array $value): bool
    {
        if (empty($value) || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item) && array_key_exists('rows', $item)) {
                return true;
            }
        }

        return false;
    }

    private function isAssocArray(array $value): bool
    {
        return ! array_is_list($value);
    }
}
