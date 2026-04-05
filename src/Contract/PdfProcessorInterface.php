<?php

declare(strict_types=1);

namespace App\Contract;

interface PdfProcessorInterface
{
    public function extractPageCount(string $filePath): int;

    /**
     * Ensures the PDF has a text layer, running OCR if needed.
     * Returns true if OCR was applied, false if text layer already existed.
     */
    public function ensureTextLayer(string $filePath): bool;

    public function extractTextFromPage(string $filePath, int $page): string;

    /**
     * Extracts text from all pages in a single pdftotext call.
     * ~20x faster than calling extractTextFromPage() per page.
     *
     * @return array<int, string> 1-based page index → text (empty string if page has no text)
     */
    public function extractAllPages(string $filePath): array;
}
