<?php

namespace App\Services\Extraction;

/**
 * Normalizes extracted data for consistency before it is stored or exported.
 *
 * Rules applied recursively across the entire data tree:
 *   - Strings:    trimmed; "null" / "n/a" / "-" → null; BOM stripped
 *   - Dates:      various formats → ISO 8601 (Y-m-d); unrecognized → original string
 *   - Numbers:    "1.234,56" or "1,234.56" → float; currency symbols stripped
 *   - Arrays:     each element normalized recursively
 *   - Null:       passed through unchanged
 */
class ResultNormalizer
{
    private const NULL_STRINGS = ['null', 'n/a', 'na', '-', '', 'none', 'undefined'];

    /** Date formats tried in order when parsing date strings. */
    private const DATE_FORMATS = [
        'd.m.Y', 'd/m/Y', 'd-m-Y',  // European DD.MM.YYYY
        'Y-m-d',                      // ISO 8601
        'm/d/Y', 'm-d-Y',            // US MM/DD/YYYY
        'd.m.y', 'd/m/y',            // Short year
        'j.n.Y', 'j/n/Y',            // Day without leading zero
        'F j, Y', 'j F Y',           // Textual month
        'd M Y', 'd M y',
    ];

    public function normalize(array $data): array
    {
        return $this->normalizeValue($data);
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeValue($v), $value);
        }

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->normalizeString($value);
        }

        return $value;
    }

    private function normalizeString(string $value): mixed
    {
        // Strip UTF-8 BOM and control characters except tabs/newlines.
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value);

        if (in_array(strtolower($value), self::NULL_STRINGS, strict: true)) {
            return null;
        }

        // Try numeric (handles "1.234,56 RON", "$1,234.56" etc.)
        $asNumber = $this->tryParseNumber($value);
        if ($asNumber !== null) {
            return $asNumber;
        }

        // Try date
        $asDate = $this->tryParseDate($value);
        if ($asDate !== null) {
            return $asDate;
        }

        return $value;
    }

    /**
     * Strips currency symbols and normalises decimal separators, then casts to float.
     * Returns null if the string does not look like a number after stripping.
     *
     * Examples:
     *   "1.234,56 RON" → 1234.56
     *   "$1,234.56"    → 1234.56
     *   "42"           → 42.0 (returned as float)
     *   "Total"        → null (not a number)
     */
    private function tryParseNumber(string $value): float|int|null
    {
        // Strip known currency symbols and codes.
        $cleaned = preg_replace('/[€$£¥₹\s]|RON|EUR|USD|GBP|CHF/u', '', $value);
        $cleaned = trim($cleaned);

        if ($cleaned === '') {
            return null;
        }

        // Detect European format: "1.234,56" — dot as thousands, comma as decimal.
        if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{1,2}$/', $cleaned)) {
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
            return (float) $cleaned;
        }

        // Detect US format: "1,234.56" — comma as thousands, dot as decimal.
        if (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{1,2}$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
            return (float) $cleaned;
        }

        // Plain integer or decimal.
        if (preg_match('/^-?\d+(\.\d+)?$/', $cleaned)) {
            return str_contains($cleaned, '.') ? (float) $cleaned : (int) $cleaned;
        }

        return null;
    }

    /**
     * Tries to parse a date string into ISO 8601 format (Y-m-d).
     * Returns null if the string doesn't match any known date format.
     */
    private function tryParseDate(string $value): ?string
    {
        // Must look like it could be a date (contains digit + separator).
        if (! preg_match('/\d{1,4}[.\-\/]\d{1,2}[.\-\/]\d{2,4}/', $value)
            && ! preg_match('/\d{1,2}\s+[A-Za-z]+\s+\d{4}/', $value)
        ) {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            $dt = \DateTime::createFromFormat($format, $value);

            if ($dt !== false) {
                // Ensure the parsed values actually match the input (no overflow).
                $errors = \DateTime::getLastErrors();
                if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }

                return $dt->format('Y-m-d');
            }
        }

        return null;
    }
}
