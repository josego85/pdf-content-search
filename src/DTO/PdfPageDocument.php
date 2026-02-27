<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PdfPageDocument
{
    /**
     * @param array<float>|null $embedding Optional 768-dim vector for semantic search
     */
    public function __construct(
        public string $id,
        public string $title,
        public int $page,
        public string $text,
        public string $path,
        public int $totalPages,
        public string $language,
        public ?array $embedding = null,
    ) {
    }
}
