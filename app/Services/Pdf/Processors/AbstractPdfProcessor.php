<?php

namespace App\Services\Pdf\Processors;

use App\Services\Pdf\Contracts\PdfProcessorInterface;
use App\Services\Pdf\Exceptions\PdfNotReadableException;
use App\Services\Pdf\ValueObjects\PdfMetadata;
use Illuminate\Support\Facades\Log;

/**
 * Clasa abstracta cu utilitati comune pentru toti processorii.
 * Nu contine logica de extragere — aceea apartine subclaselor.
 */
abstract class AbstractPdfProcessor implements PdfProcessorInterface
{
    /**
     * Parseaza output-ul pdfinfo si returneaza un PdfMetadata.
     */
    protected function parseMetadataFromPdfInfo(string $filePath): PdfMetadata
    {
        $this->assertFileReadable($filePath);

        $pdfInfoPath = config('ai.pdf_processor.pdfinfo_path', '/usr/bin/pdfinfo');
        $command     = escapeshellarg($pdfInfoPath) . ' ' . escapeshellarg($filePath) . ' 2>&1';

        exec($command, $output, $exitCode);

        $raw         = implode("
", $output);
        $isEncrypted = (bool) preg_match('/^Encrypted:\s+yes/im', $raw);

        $pageCount = 0;
        if (preg_match('/Pages:\s+(\d+)/i', $raw, $m)) {
            $pageCount = (int) $m[1];
        }

        $title   = $this->extractPdfInfoField($raw, 'Title');
        $author  = $this->extractPdfInfoField($raw, 'Author');
        $creator = $this->extractPdfInfoField($raw, 'Creator');

        return new PdfMetadata(
            filePath:        $filePath,
            pageCount:       max($pageCount, 1),
            fileSizeBytes:   filesize($filePath),
            isEncrypted:     $isEncrypted,
            isLikelyScanned: false,
            title:           $title,
            author:          $author,
            creator:         $creator,
        );
    }

    /**
     * Ruleaza un command de shell si returneaza [output_string, exit_code].
     */
    protected function runCommand(string $command): array
    {
        Log::debug("[{$this->getName()}] Running: {$command}");

        $output   = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        $outputStr = implode("
", $output);

        if ($exitCode !== 0) {
            Log::warning("[{$this->getName()}] Command exited {$exitCode}: {$outputStr}");
        }

        return [$outputStr, $exitCode];
    }

    /**
     * Verifica ca fisierul exista si e lizibil.
     *
     * @throws PdfNotReadableException
     */
    protected function assertFileReadable(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw PdfNotReadableException::fileNotFound($filePath);
        }

        if (! is_readable($filePath)) {
            throw PdfNotReadableException::fileNotReadable($filePath);
        }
    }

    /**
     * Cronometreaza o operatiune si returneaza [result, elapsedMs].
     */
    protected function timed(callable $fn): array
    {
        $start  = microtime(true);
        $result = $fn();
        $ms     = round((microtime(true) - $start) * 1000, 2);

        return [$result, $ms];
    }

    private function extractPdfInfoField(string $raw, string $field): ?string
    {
        if (preg_match('/^' . preg_quote($field, '/') . ':\s*(.+)$/im', $raw, $m)) {
            $value = trim($m[1]);
            return $value !== '' ? $value : null;
        }

        return null;
    }
}
