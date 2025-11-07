<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Parses user search queries to extract operators and terms.
 * Single Responsibility: Parse and normalize search input.
 */
final readonly class QueryParser
{
    /**
     * Parse query string into structured search terms.
     *
     * Supports:
     * - "exact phrase" - phrase matching
     * - +required - term must be present
     * - -excluded - term must not be present
     * - regular terms - normal search
     *
     * @return array{
     *     phrases: array<string>,
     *     required: array<string>,
     *     excluded: array<string>,
     *     regular: array<string>,
     *     hasOperators: bool
     * }
     */
    public function parse(string $query): array
    {
        $query = trim($query);

        $result = [
            'phrases' => [],
            'required' => [],
            'excluded' => [],
            'regular' => [],
            'hasOperators' => false,
        ];

        // Extract phrases in quotes
        if (preg_match_all('/"([^"]+)"/', $query, $matches)) {
            $result['phrases'] = $matches[1];
            $result['hasOperators'] = true;
            // Remove phrases from query
            $query = preg_replace('/"[^"]+"/', '', $query);
        }

        // Extract individual terms
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            if (empty($term)) {
                continue;
            }

            // Required term (+term)
            if (str_starts_with($term, '+')) {
                $result['required'][] = substr($term, 1);
                $result['hasOperators'] = true;
                continue;
            }

            // Excluded term (-term)
            if (str_starts_with($term, '-')) {
                $result['excluded'][] = substr($term, 1);
                $result['hasOperators'] = true;
                continue;
            }

            // Regular term
            $result['regular'][] = $term;
        }

        return $result;
    }

    /**
     * Get clean query without operators for simple searches.
     */
    public function getCleanQuery(string $query): string
    {
        // Remove quotes
        $clean = str_replace('"', '', $query);

        // Remove +/- operators
        $clean = preg_replace('/[+-](\S+)/', '$1', $clean);

        // Normalize spaces
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }
}
