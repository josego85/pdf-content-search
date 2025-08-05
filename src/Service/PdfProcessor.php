<?php

declare(strict_types=1);

namespace App\Service;

class PdfProcessor
{
    public function extractPageCount(string $filePath): int
    {
        $pageCountOutput = shell_exec('pdfinfo ' . escapeshellarg($filePath));
        preg_match('/Pages:\\s+(\\d+)/i', $pageCountOutput, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function extractTextFromPage(string $filePath, int $page): string
    {
        $text = shell_exec("pdftotext -layout -f $page -l $page " . escapeshellarg($filePath) . ' -');

        return trim($text);
    }
}
