<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\RankFusionServiceInterface;

/**
 * Reciprocal Rank Fusion (RRF) implementation.
 * Merges multiple search result sets using RRF algorithm.
 *
 * RRF Score Formula: score(doc) = Σ weight_i / (k + rank_i)
 * where k = 60 (standard constant), rank_i = position in result set i
 */
final readonly class ReciprocalRankFusionService implements RankFusionServiceInterface
{
    private const int RRF_CONSTANT = 60;

    public function merge(array $resultSets, array $weights = []): array
    {
        if (empty($resultSets)) {
            return [];
        }

        // Default to equal weights if not provided
        if (empty($weights)) {
            $weights = array_fill(0, count($resultSets), 1.0);
        }

        $scores = [];
        $documents = [];

        // Calculate RRF scores for each document across all result sets
        foreach ($resultSets as $setIndex => $results) {
            $weight = $weights[$setIndex] ?? 1.0;

            foreach ($results as $rank => $document) {
                $docId = $document['_id'];

                // RRF formula: weight / (k + rank)
                $rrfScore = $weight / (self::RRF_CONSTANT + $rank + 1);

                // Accumulate scores for documents appearing in multiple result sets
                if (!isset($scores[$docId])) {
                    $scores[$docId] = 0.0;
                    $documents[$docId] = $document;
                } else {
                    // Merge highlights from multiple sources
                    $documents[$docId] = $this->mergeDocuments($documents[$docId], $document);
                }

                $scores[$docId] += $rrfScore;
            }
        }

        // Sort by RRF score (descending)
        arsort($scores);

        // Build final result array with RRF scores
        $mergedResults = [];
        foreach ($scores as $docId => $score) {
            $document = $documents[$docId];
            $document['_rrf_score'] = $score;
            $mergedResults[] = $document;
        }

        return $mergedResults;
    }

    /**
     * Merge two document objects, combining highlights and preserving best source data.
     */
    private function mergeDocuments(array $doc1, array $doc2): array
    {
        // Merge highlight arrays
        if (isset($doc1['highlight']) && isset($doc2['highlight'])) {
            foreach ($doc2['highlight'] as $field => $fragments) {
                if (isset($doc1['highlight'][$field])) {
                    // Combine fragments and remove duplicates
                    $doc1['highlight'][$field] = array_unique(
                        array_merge($doc1['highlight'][$field], $fragments)
                    );
                } else {
                    $doc1['highlight'][$field] = $fragments;
                }
            }
        } elseif (isset($doc2['highlight'])) {
            $doc1['highlight'] = $doc2['highlight'];
        }

        // Use higher _score if available
        if (isset($doc1['_score']) && isset($doc2['_score'])) {
            $doc1['_score'] = max($doc1['_score'], $doc2['_score']);
        } elseif (isset($doc2['_score'])) {
            $doc1['_score'] = $doc2['_score'];
        }

        return $doc1;
    }
}
