<?php

declare(strict_types=1);

namespace App\Service;

class PdfProcessor
{
    public function __construct(
        private readonly string $ocrLanguages,
        private readonly int $minTextLength,
    ) {
    }

    public function extractPageCount(string $filePath): int
    {
        $pageCountOutput = shell_exec('pdfinfo ' . escapeshellarg($filePath) . ' 2>/dev/null');

        if (!is_string($pageCountOutput)) {
            return 0;
        }

        preg_match('/Pages:\\s+(\\d+)/i', $pageCountOutput, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * Adds an invisible text layer to a scanned PDF using ocrmypdf.
     * After this, pdftotext and PDF.js highlights work normally.
     */
    public function ensureTextLayer(string $filePath): bool
    {
        $text = $this->extractWithPdftotext($filePath, 1);

        if (strlen($text) >= $this->minTextLength) {
            return false;
        }

        $tmpOutput = $filePath . '.ocr.pdf';
        $exitCode = 0;
        exec(sprintf(
            'ocrmypdf --skip-text -l %s %s %s 2>&1',
            escapeshellarg($this->ocrLanguages),
            escapeshellarg($filePath),
            escapeshellarg($tmpOutput)
        ), $output, $exitCode);

        if ($exitCode === 0 && file_exists($tmpOutput)) {
            rename($tmpOutput, $filePath);

            return true;
        }

        if (file_exists($tmpOutput)) {
            unlink($tmpOutput);
        }

        return false;
    }

    public function extractTextFromPage(string $filePath, int $page): string
    {
        $text = $this->extractWithPdftotext($filePath, $page);

        return $text;
    }

    private function extractWithPdftotext(string $filePath, int $page): string
    {
        $text = shell_exec(sprintf(
            'pdftotext -layout -f %d -l %d %s - 2>/dev/null',
            $page,
            $page,
            escapeshellarg($filePath)
        ));

        return is_string($text) ? trim($text) : '';
    }
}
