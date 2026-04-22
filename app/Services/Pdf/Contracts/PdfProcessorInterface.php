<?php

namespace App\Services\Pdf\Contracts;

use App\Services\Pdf\ValueObjects\PdfMetadata;
use App\Services\Pdf\ValueObjects\PdfProcessingResult;

/**
 * Contract pentru orice implementare de PDF Processor.
 *
 * Pentru a adăuga un processor nou:
 *   1. Implementează această interfață
 *   2. Înregistrează-l în PdfProcessorFactory::PROCESSORS
 *   3. Adaugă driver-ul în config/ai.php → pdf_processor.driver
 */
interface PdfProcessorInterface
{
    /**
     * Extrage tot textul din PDF.
     *
     * @throws \App\Services\Pdf\Exceptions\PdfNotReadableException
     * @throws \App\Services\Pdf\Exceptions\PdfProcessingException
     */
    public function extract(string $filePath): PdfProcessingResult;

    /**
     * Extrage text dintr-un interval de pagini (1-based, inclusiv).
     *
     * @throws \App\Services\Pdf\Exceptions\PdfNotReadableException
     * @throws \App\Services\Pdf\Exceptions\PdfProcessingException
     */
    public function extractPages(string $filePath, int $fromPage, int $toPage): PdfProcessingResult;

    /**
     * Returnează metadata fișierului fără a extrage text.
     *
     * @throws \App\Services\Pdf\Exceptions\PdfNotReadableException
     */
    public function getMetadata(string $filePath): PdfMetadata;

    /**
     * Verifică dacă procesorul poate fi folosit în mediul curent
     * (binarul necesar e instalat, permisiunile sunt ok etc.).
     */
    public function isAvailable(): bool;

    /**
     * Identificatorul unic al procesorului (ex: "native", "ocr", "auto").
     * Apare în PdfProcessingResult::$processorUsed și în logs.
     */
    public function getName(): string;
}
