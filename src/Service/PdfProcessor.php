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
        $pageCountOutput = shell_exec('pdfinfo ' . escapeshellarg($filePath));

        if (!is_string($pageCountOutput)) {
            return 0;
        }

        preg_match('/Pages:\\s+(\\d+)/i', $pageCountOutput, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function extractTextFromPage(string $filePath, int $page): string
    {
        $text = $this->extractWithPdftotext($filePath, $page);

        if (strlen($text) < $this->minTextLength) {
            $ocrText = $this->extractWithOcr($filePath, $page);

            if (strlen($ocrText) > strlen($text)) {
                $text = $ocrText;
            }
        }

        return $text;
    }

    private function extractWithPdftotext(string $filePath, int $page): string
    {
        $text = shell_exec(sprintf(
            'pdftotext -layout -f %d -l %d %s -',
            $page,
            $page,
            escapeshellarg($filePath)
        ));

        return is_string($text) ? trim($text) : '';
    }

    private function extractWithOcr(string $filePath, int $page): string
    {
        $tmpPrefix = sys_get_temp_dir() . '/ocr_' . getmypid();

        try {
            // Convert PDF page to PNG image (pdftoppm is from poppler-utils)
            shell_exec(sprintf(
                'pdftoppm -f %d -l %d -png -r 300 -singlefile %s %s',
                $page,
                $page,
                escapeshellarg($filePath),
                escapeshellarg($tmpPrefix)
            ));

            $imagePath = $tmpPrefix . '.png';

            if (!file_exists($imagePath)) {
                return '';
            }

            // Run Tesseract OCR on the image
            $text = shell_exec(sprintf(
                'tesseract %s stdout -l %s 2>/dev/null',
                escapeshellarg($imagePath),
                $this->ocrLanguages
            ));

            return is_string($text) ? trim($text) : '';
        } finally {
            $imagePath = $tmpPrefix . '.png';

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
}
