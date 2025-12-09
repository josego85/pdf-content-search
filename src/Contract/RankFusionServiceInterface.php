<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Interface for rank fusion algorithms.
 * Merges multiple search result sets into a single ranked list.
 */
interface RankFusionServiceInterface
{
    /**
     * Merge multiple result sets using a rank fusion algorithm.
     *
     * @param array<int, array<int, array<string, mixed>>> $resultSets Array of result sets to merge (each is an array of documents)
     * @param array<int, float> $weights Optional weights for each result set (default: equal weight)
     *
     * @return array<int, array<string, mixed>> Merged and re-ranked results
     */
    public function merge(array $resultSets, array $weights = []): array;
}
