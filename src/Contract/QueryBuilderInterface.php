<?php

declare(strict_types=1);

namespace App\Contract;

use App\Search\SearchStrategy;

/**
 * Query builder interface for search engines.
 * Dependency Inversion Principle: Depend on abstractions, not implementations.
 */
interface QueryBuilderInterface
{
    /**
     * Build search query parameters for the underlying search engine.
     *
     * @param string $query User search query
     * @param SearchStrategy $strategy Search strategy to apply
     *
     * @return array Engine-specific query parameters
     */
    public function build(string $query, SearchStrategy $strategy = SearchStrategy::HYBRID): array;
}
