<?php

namespace App\Services\Extraction;

/**
 * Extracts a raw JSON string from an AI response that may contain:
 *   - Markdown code fences (```json ... ``` or ``` ... ```)
 *   - Explanatory text before or after the JSON
 *   - A mix of prose and embedded JSON
 *
 * Does NOT validate the extracted JSON — that is JsonValidator's job.
 */
class JsonExtractor
{
    /**
     * Returns the first JSON object or array found in the raw text.
     * Returns null if no JSON-like structure is detected.
     */
    public function extract(string $raw): ?string
    {
        $raw = trim($raw);

        // 1. Try to pull out a fenced code block first (most common AI pattern).
        $fromFence = $this->extractFromFence($raw);
        if ($fromFence !== null) {
            return trim($fromFence);
        }

        // 2. Try to find the outermost { ... } or [ ... ] in the whole text.
        $fromBraces = $this->extractFromBraces($raw);
        if ($fromBraces !== null) {
            return trim($fromBraces);
        }

        // 3. Last resort: if the whole trimmed string starts with { or [, return as-is.
        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            return $raw;
        }

        return null;
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * Extracts content from ```json ... ``` or ``` ... ``` blocks.
     */
    private function extractFromFence(string $text): ?string
    {
        // Match ```json ... ``` (most specific first)
        if (preg_match('/```json\s*([\s\S]+?)\s*```/i', $text, $matches)) {
            return $matches[1];
        }

        // Match ``` ... ``` (generic fence)
        if (preg_match('/```\s*([\s\S]+?)\s*```/', $text, $matches)) {
            $inner = trim($matches[1]);
            // Only return if the content looks like JSON, not code in another language.
            if (str_starts_with($inner, '{') || str_starts_with($inner, '[')) {
                return $inner;
            }
        }

        return null;
    }

    /**
     * Finds the outermost { ... } or [ ... ] by tracking brace depth.
     * Picks whichever opener appears first in the text so that a JSON array
     * like [{"id":1}] is not confused with the inner object {"id":1}.
     * Handles nested objects/arrays correctly.
     */
    private function extractFromBraces(string $text): ?string
    {
        // Pick the opener that appears earliest in the text.
        $posObj   = mb_strpos($text, '{');
        $posArr   = mb_strpos($text, '[');

        if ($posObj === false && $posArr === false) {
            return null;
        }

        if ($posObj === false) {
            $open = '[';
        } elseif ($posArr === false) {
            $open = '{';
        } else {
            $open = $posObj < $posArr ? '{' : '[';
        }

        $openers = [$open];

        foreach ($openers as $open) {
            $close = $open === '{' ? '}' : ']';
            $start = mb_strpos($text, $open);

            if ($start === false) {
                continue;
            }

            $depth  = 0;
            $inStr  = false;
            $escape = false;
            $len    = mb_strlen($text);

            for ($i = $start; $i < $len; $i++) {
                $char = mb_substr($text, $i, 1);

                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\' && $inStr) {
                    $escape = true;
                    continue;
                }

                if ($char === '"') {
                    $inStr = ! $inStr;
                    continue;
                }

                if ($inStr) {
                    continue;
                }

                if ($char === $open) {
                    $depth++;
                } elseif ($char === $close) {
                    $depth--;
                    if ($depth === 0) {
                        return mb_substr($text, $start, $i - $start + 1);
                    }
                }
            }
        }

        return null;
    }
}
