<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ReciprocalRankFusionService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ReciprocalRankFusionService.
 * Validates RRF algorithm for merging search results.
 */
final class ReciprocalRankFusionServiceTest extends TestCase
{
    private ReciprocalRankFusionService $service;

    protected function setUp(): void
    {
        $this->service = new ReciprocalRankFusionService();
    }

    public function testMergeWithEmptyResultSets(): void
    {
        $result = $this->service->merge([]);

        $this->assertSame([], $result);
    }

    public function testMergeSingleResultSet(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(2, $result);
        $this->assertSame('doc1', $result[0]['_id']);
        $this->assertSame('doc2', $result[1]['_id']);
        $this->assertArrayHasKey('_rrf_score', $result[0]);
        $this->assertArrayHasKey('_rrf_score', $result[1]);
    }

    public function testMergeMultipleResultSetsWithEqualWeights(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
            ],
            [
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
                ['_id' => 'doc3', '_source' => ['title' => 'Document 3']],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(3, $result);
        // doc2 should be first because it appears in both result sets
        $this->assertSame('doc2', $result[0]['_id']);
        $this->assertGreaterThan($result[1]['_rrf_score'], $result[0]['_rrf_score']);
    }

    public function testMergeWithCustomWeights(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
            [
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
            ],
        ];

        // Higher weight for second result set
        $weights = [1.0, 2.0];

        $result = $this->service->merge($resultSets, $weights);

        $this->assertCount(2, $result);
        // doc2 should have higher score due to higher weight
        $this->assertSame('doc2', $result[0]['_id']);
        $this->assertGreaterThan($result[1]['_rrf_score'], $result[0]['_rrf_score']);
    }

    public function testRrfScoreCalculation(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
        ];

        $result = $this->service->merge($resultSets);

        // RRF formula: 1.0 / (60 + 0 + 1) = 1/61 ≈ 0.0163934
        $expectedScore = 1.0 / 61;
        $this->assertEqualsWithDelta($expectedScore, $result[0]['_rrf_score'], 0.0001);
    }

    public function testMergeHighlights(): void
    {
        $resultSets = [
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    'highlight' => [
                        'text' => ['Fragment from <em>lexical</em> search'],
                    ],
                ],
            ],
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    'highlight' => [
                        'text' => ['Fragment from <em>semantic</em> search'],
                    ],
                ],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('highlight', $result[0]);
        $this->assertCount(2, $result[0]['highlight']['text']);
        $this->assertContains('Fragment from <em>lexical</em> search', $result[0]['highlight']['text']);
        $this->assertContains('Fragment from <em>semantic</em> search', $result[0]['highlight']['text']);
    }

    public function testMergeHighlightsRemovesDuplicates(): void
    {
        $resultSets = [
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    'highlight' => [
                        'text' => ['Same <em>fragment</em>'],
                    ],
                ],
            ],
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    'highlight' => [
                        'text' => ['Same <em>fragment</em>', 'Different fragment'],
                    ],
                ],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]['highlight']['text']);
        $this->assertContains('Same <em>fragment</em>', $result[0]['highlight']['text']);
        $this->assertContains('Different fragment', $result[0]['highlight']['text']);
    }

    public function testMergeScoresKeepsMaximum(): void
    {
        $resultSets = [
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    '_score' => 10.5,
                ],
            ],
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    '_score' => 8.3,
                ],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertSame(10.5, $result[0]['_score']);
    }

    public function testMergePreservesDocumentSource(): void
    {
        $resultSets = [
            [
                [
                    '_id' => 'doc1',
                    '_source' => [
                        'title' => 'Document 1',
                        'text' => 'Content',
                        'page' => 5,
                    ],
                ],
            ],
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                ],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertSame('Document 1', $result[0]['_source']['title']);
        $this->assertSame('Content', $result[0]['_source']['text']);
        $this->assertSame(5, $result[0]['_source']['page']);
    }

    public function testMergeOrdersByRrfScoreDescending(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
                ['_id' => 'doc3', '_source' => ['title' => 'Document 3']],
            ],
            [
                ['_id' => 'doc3', '_source' => ['title' => 'Document 3']],
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(3, $result);
        // Verify descending order
        $this->assertGreaterThanOrEqual($result[1]['_rrf_score'], $result[0]['_rrf_score']);
        $this->assertGreaterThanOrEqual($result[2]['_rrf_score'], $result[1]['_rrf_score']);
    }

    public function testMergeWithMissingWeights(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
            [
                ['_id' => 'doc2', '_source' => ['title' => 'Document 2']],
            ],
            [
                ['_id' => 'doc3', '_source' => ['title' => 'Document 3']],
            ],
        ];

        // Only provide weights for 2 result sets, third should default to 1.0
        $weights = [1.0, 2.0];

        $result = $this->service->merge($resultSets, $weights);

        $this->assertCount(3, $result);
        // All documents should be present with RRF scores
        $this->assertArrayHasKey('_rrf_score', $result[0]);
        $this->assertArrayHasKey('_rrf_score', $result[1]);
        $this->assertArrayHasKey('_rrf_score', $result[2]);
    }

    public function testMergeHandlesDocumentsWithoutHighlights(): void
    {
        $resultSets = [
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
            [
                ['_id' => 'doc1', '_source' => ['title' => 'Document 1']],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('highlight', $result[0]);
    }

    public function testMergeHandlesOneDocumentWithHighlightOtherWithout(): void
    {
        $resultSets = [
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                    'highlight' => [
                        'text' => ['Fragment with <em>highlight</em>'],
                    ],
                ],
            ],
            [
                [
                    '_id' => 'doc1',
                    '_source' => ['title' => 'Document 1'],
                ],
            ],
        ];

        $result = $this->service->merge($resultSets);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('highlight', $result[0]);
        $this->assertSame(['Fragment with <em>highlight</em>'], $result[0]['highlight']['text']);
    }
}
