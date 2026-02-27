<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\PdfPageDocument;

interface PdfIndexerInterface
{
    /**
     * Index a collection of PDF pages into the search engine.
     * Implementations are free to choose their own batching and optimization strategies.
     *
     * @param PdfPageDocument[] $pages
     */
    public function indexPages(array $pages): void;
}
