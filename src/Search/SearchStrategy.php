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

    /**
     * Semantic search using vector embeddings.
     * Finds conceptually similar content without exact keyword matches.
     * Best for exploring related topics and natural language queries.
     */
    case SEMANTIC = 'semantic';

    /**
     * Hybrid AI search: Combines lexical (keyword) + semantic (vector) search with RRF.
     * Merges results from both approaches for optimal relevance.
     * Best for production use - highest quality results.
     */
    case HYBRID_AI = 'hybrid_ai';
}
