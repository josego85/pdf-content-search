<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\SearchResult;

interface SearchEngineInterface
{
    /**
     * @param array<string, mixed> $query
     */
    public function search(array $query): SearchResult;
}
