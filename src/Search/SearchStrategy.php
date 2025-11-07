<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Search strategy types.
 * Single Responsibility: Define available search strategies.
 */
enum SearchStrategy: string
{
    /**
     * Hybrid search: Exact matches first, then fuzzy for long words.
     * Best for general use - balances precision and recall.
     */
    case HYBRID = 'hybrid';

    /**
     * Exact match only - no typo tolerance.
     * Best for legal/academic documents where precision is critical.
     */
    case EXACT = 'exact';

    /**
     * Prefix matching for autocomplete.
     * Best for search-as-you-type features.
     */
    case PREFIX = 'prefix';
}
