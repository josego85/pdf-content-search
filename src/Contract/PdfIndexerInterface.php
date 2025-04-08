<?php

namespace App\Contract;

interface PdfIndexerInterface
{
    public function indexPdfPage(string $id, string $title, int $page, string $text, string $path, int $totalPages): void;
}
