<?php

namespace App\Services\Extraction;

use App\Models\ExtractionTemplate;
use App\Services\Ai\ValueObjects\AiRequest;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;

/**
 * Builds an AiRequest by combining an extraction template with the PDF text.
 *
 * Template placeholders:
 *   {pdf_text}      — replaced with the (truncated) PDF text
 *   {output_schema} — replaced with a pretty-printed JSON Schema
 *
 * Text truncation respects `config('ai.extraction.text_max_chars')` and trims
 * at the last whitespace boundary to avoid cutting mid-word.
 */
class PromptBuilder
{
    private const PLACEHOLDER_TEXT   = '{pdf_text}';
    private const PLACEHOLDER_SCHEMA = '{output_schema}';

    public function build(ExtractionTemplate $template, PdfProcessingResult $pdfResult): AiRequest
    {
        $text   = $this->truncateText($pdfResult->text);
        $schema = $this->formatSchema($template->output_schema);

        $prompt = str_replace(
            [self::PLACEHOLDER_TEXT, self::PLACEHOLDER_SCHEMA],
            [$text, $schema],
            $template->prompt_template,
        );

        return new AiRequest(
            prompt:      $prompt,
            temperature: 0.1,  // Low temperature yields deterministic, structured output.
        );
    }

    /**
     * Builds a corrective follow-up prompt when the AI returned invalid JSON.
     * Asks the model to fix its previous response rather than starting from scratch.
     */
    public function buildCorrectionPrompt(
        ExtractionTemplate  $template,
        PdfProcessingResult $pdfResult,
        string              $invalidResponse,
        int                 $attemptNumber,
    ): AiRequest {
        $originalRequest = $this->build($template, $pdfResult);

        $correction = implode("\n", [
            "IMPORTANT: Your previous response (attempt #{$attemptNumber}) was not valid JSON.",
            "Previous response:",
            "---",
            mb_substr($invalidResponse, 0, 500),
            "---",
            "Return ONLY a raw JSON object. No markdown. No explanation. No code blocks.",
            "",
            $originalRequest->prompt,
        ]);

        return new AiRequest(
            prompt:      $correction,
            temperature: 0.0,  // Zero temperature for maximum determinism on retries.
        );
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function truncateText(string $text): string
    {
        $maxChars = (int) config('ai.extraction.text_max_chars', 8000);

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxChars);

        // Trim at the last whitespace to avoid cutting mid-word.
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxChars * 0.8) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated . "\n[... text truncated ...]";
    }

    private function formatSchema(array $schema): string
    {
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
