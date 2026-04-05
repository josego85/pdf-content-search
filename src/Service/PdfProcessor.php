<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PdfProcessorInterface;
use Symfony\Component\Process\Process;

final readonly class PdfProcessor implements PdfProcessorInterface
{
    public function __construct(
        private string $ocrLanguages,
        private int $minTextLength,
    ) {
    }

    public function extractPageCount(string $filePath): int
    {
        $process = new Process(['pdfinfo', $filePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            return 0;
        }

        preg_match('/Pages:\\s+(\\d+)/i', $process->getOutput(), $matches);

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

        $process = new Process([
            'ocrmypdf',
            '--skip-text',
            '-l', $this->ocrLanguages,
            $filePath,
            $tmpOutput,
        ]);
        $process->setTimeout(300);
        $process->run();

        if ($process->getExitCode() === 0 && file_exists($tmpOutput)) {
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
        return $this->extractWithPdftotext($filePath, $page);
    }

    /**
     * Extracts all pages in one pdftotext call and splits by form-feed (\f).
     * ~20x faster than one call per page for large PDFs.
     *
     * {@inheritDoc}
     */
    public function extractAllPages(string $filePath): array
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            $filePath,
            '-',
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $pages = [];
        // pdftotext separates pages with \f (form feed, ASCII 12)
        $rawPages = explode("\f", $process->getOutput());

        foreach ($rawPages as $index => $raw) {
            $text = trim($raw);

            // 1-based page numbers; pdftotext appends a trailing \f so the last element is empty
            if ($text !== '') {
                $pages[$index + 1] = $text;
            }
        }

        return $pages;
    }

    private function extractWithPdftotext(string $filePath, int $page): string
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            '-f', (string) $page,
            '-l', (string) $page,
            $filePath,
            '-',
        ]);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : '';
    }
}
