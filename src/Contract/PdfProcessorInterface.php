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
}
