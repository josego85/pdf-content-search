<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\QueryBuilderInterface;

/**
 * Builds Elasticsearch search queries.
 * Single Responsibility: Construct Elasticsearch DSL queries.
 * Open/Closed: New strategies can be added without modifying existing code.
 * Dependency Inversion: Implements QueryBuilderInterface for decoupling.
 */
final readonly class SearchQueryBuilder implements QueryBuilderInterface
{
    private const FUZZY_MIN_LENGTH = 5; // Only apply fuzzy to words with 5+ chars

    public function __construct(
        private QueryParser $parser,
        private string $pdfPagesIndex,
        private int $maxResults = 100
    ) {
    }

    /**
     * Build complete search parameters for Elasticsearch.
     *
     * @return array<string, mixed>
     */
    public function build(string $query, SearchStrategy $strategy = SearchStrategy::HYBRID): array
    {
        $parsedQuery = $this->parser->parse($query);

        return [
            'index' => $this->pdfPagesIndex,
            'body' => [
                'query' => $this->buildQuery($query, $parsedQuery, $strategy),
                'highlight' => $this->buildHighlight(),
                'size' => $this->maxResults,
            ],
        ];
    }

    /**
     * Build query clause based on strategy.
     *
     * @param array<string, mixed> $parsedQuery
     *
     * @return array<string, mixed>
     */
    private function buildQuery(string $query, array $parsedQuery, SearchStrategy $strategy): array
    {
        // If user used operators, build structured query
        if ($parsedQuery['hasOperators']) {
            return $this->buildStructuredQuery($parsedQuery);
        }

        // Otherwise, use strategy-based query
        return match ($strategy) {
            SearchStrategy::HYBRID => $this->buildHybridQuery($query),
            SearchStrategy::EXACT => $this->buildExactQuery($query),
            SearchStrategy::PREFIX => $this->buildPrefixQuery($query),
            default => $this->buildHybridQuery($query),
        };
    }

    /**
     * Hybrid: Exact matches first, fuzzy for long words.
     * Best balance between precision and recall.
     *
     * @return array<string, mixed>
     */
    private function buildHybridQuery(string $query): array
    {
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hasLongWords = count(array_filter($words, static fn ($w) => mb_strlen($w) >= self::FUZZY_MIN_LENGTH)) > 0;

        return [
            'bool' => [
                'should' => [
                    // Priority 1: Exact phrase (boost x10)
                    [
                        'match_phrase' => [
                            'text' => [
                                'query' => $query,
                                'boost' => 10,
                            ],
                        ],
                    ],
                    // Priority 2: All words exact match (boost x5)
                    [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^3', 'text'],
                            'operator' => 'and',
                            'boost' => 5,
                        ],
                    ],
                    // Priority 3: Fuzzy ONLY for long words (boost x1)
                    ...($hasLongWords ? [[
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^2', 'text'],
                            'fuzziness' => 1,
                            'operator' => 'and',
                            'boost' => 1,
                        ],
                    ]] : []),
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * Exact: No fuzzy matching, exact words only.
     * Best for legal/academic precision.
     *
     * @return array<string, mixed>
     */
    private function buildExactQuery(string $query): array
    {
        return [
            'multi_match' => [
                'query' => $query,
                'fields' => ['title^2', 'text'],
                'operator' => 'and',
            ],
        ];
    }

    /**
     * Prefix: Match word beginnings with wildcard support.
     * Supports both * (multiple chars) and ? (single char) wildcards.
     *
     * @return array<string, mixed>
     */
    private function buildPrefixQuery(string $query): array
    {
        $trimmedQuery = trim($query);

        // If query contains ? wildcard, use Elasticsearch wildcard query
        if (str_contains($trimmedQuery, '?')) {
            return [
                'query_string' => [
                    'query' => $trimmedQuery,
                    'fields' => ['title^2', 'text'],
                    'analyze_wildcard' => true,
                ],
            ];
        }

        // For simple * at end (most common case), use optimized phrase_prefix
        // Strip trailing * from query (user-friendly syntax: java* → finds java, javascript, javabean)
        $cleanQuery = rtrim($trimmedQuery, '*');

        return [
            'multi_match' => [
                'query' => $cleanQuery,
                'fields' => ['title^2', 'text'],
                'type' => 'phrase_prefix',
                'max_expansions' => 50,
            ],
        ];
    }

    /**
     * Build structured query from parsed operators.
     *
     * @param array<string, mixed> $parsedQuery
     *
     * @return array<string, mixed>
     */
    private function buildStructuredQuery(array $parsedQuery): array
    {
        $must = [];
        $mustNot = [];
        $should = [];

        // Required terms
        foreach ($parsedQuery['required'] as $term) {
            $must[] = ['match' => ['text' => $term]];
        }

        // Excluded terms
        foreach ($parsedQuery['excluded'] as $term) {
            $mustNot[] = ['match' => ['text' => $term]];
        }

        // Phrases (highest priority)
        foreach ($parsedQuery['phrases'] as $phrase) {
            $must[] = [
                'match_phrase' => [
                    'text' => [
                        'query' => $phrase,
                        'boost' => 10,
                    ],
                ],
            ];
        }

        // Regular terms (should match)
        foreach ($parsedQuery['regular'] as $term) {
            $should[] = ['match' => ['text' => $term]];
        }

        return [
            'bool' => array_filter([
                'must' => !empty($must) ? $must : null,
                'must_not' => !empty($mustNot) ? $mustNot : null,
                'should' => !empty($should) ? $should : null,
                'minimum_should_match' => !empty($should) && empty($must) ? 1 : null,
            ]),
        ];
    }

    /**
     * Build highlight configuration.
     *
     * @return array<string, mixed>
     */
    private function buildHighlight(): array
    {
        return [
            'fields' => [
                'text' => [
                    'fragment_size' => 150,
                    'number_of_fragments' => 3,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>'],
                ],
            ],
        ];
    }
}
