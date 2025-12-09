<?php

declare(strict_types=1);

namespace App\Contract;

interface PdfIndexerInterface
{
    /**
     * Index a single PDF page with optional embedding vector.
     *
     * @param array<float>|null $embedding Optional 768-dim embedding vector for semantic search
     */
    public function indexPdfPage(string $id, string $title, int $page, string $text, string $path, int $totalPages, string $language = 'unknown', ?array $embedding = null): void;
}
