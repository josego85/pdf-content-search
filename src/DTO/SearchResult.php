<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SearchResult
{
    /**
     * @param array<int, array<string, mixed>> $hits
     */
    public function __construct(
        public array $hits,
        public int $total,
    ) {
    }
}
