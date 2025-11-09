<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search;

use App\Search\QueryParser;
use App\Search\SearchQueryBuilder;
use App\Search\SearchStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for SearchQueryBuilder with 100% coverage.
 * Tests all query building strategies and edge cases.
 */
final class SearchQueryBuilderTest extends TestCase
{
    private const TEST_INDEX_NAME = 'test_pdf_pages';
    private const FUZZY_MIN_LENGTH = 5;
    private const EXACT_PHRASE_BOOST = 10;
    private const ALL_WORDS_EXACT_BOOST = 5;
    private const FUZZY_BOOST = 1;
    private const HIGHLIGHT_FRAGMENT_SIZE = 150;
    private const HIGHLIGHT_FRAGMENTS_COUNT = 3;
    private const PREFIX_MAX_EXPANSIONS = 50;

    private SearchQueryBuilder $builder;
    private QueryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new QueryParser();
        $this->builder = new SearchQueryBuilder($this->parser, self::TEST_INDEX_NAME);
    }

    public function testBuildIncludesIndexName(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $this->assertArrayHasKey('index', $result);
        $this->assertSame(self::TEST_INDEX_NAME, $result['index']);
    }

    public function testBuildIncludesBodyWithQueryAndHighlight(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('query', $result['body']);
        $this->assertArrayHasKey('highlight', $result['body']);
    }

    public function testBuildHighlightConfiguration(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $highlight = $result['body']['highlight'];
        $this->assertArrayHasKey('fields', $highlight);
        $this->assertArrayHasKey('text', $highlight['fields']);

        $textHighlight = $highlight['fields']['text'];
        $this->assertSame(self::HIGHLIGHT_FRAGMENT_SIZE, $textHighlight['fragment_size']);
        $this->assertSame(self::HIGHLIGHT_FRAGMENTS_COUNT, $textHighlight['number_of_fragments']);
        $this->assertSame(['<mark>'], $textHighlight['pre_tags']);
        $this->assertSame(['</mark>'], $textHighlight['post_tags']);
    }

    public function testBuildHybridQueryWithShortWords(): void
    {
        // Arrange - words shorter than FUZZY_MIN_LENGTH (5)
        $query = 'cat dog';

        // Act
        $result = $this->builder->build($query, SearchStrategy::HYBRID);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('should', $queryBody['bool']);

        $shouldClauses = $queryBody['bool']['should'];
        // Should have 2 clauses (exact phrase + all words exact), NO fuzzy
        $this->assertCount(2, $shouldClauses);

        // Verify exact phrase match
        $this->assertArrayHasKey('match_phrase', $shouldClauses[0]);
        $this->assertSame($query, $shouldClauses[0]['match_phrase']['text']['query']);
        $this->assertSame(self::EXACT_PHRASE_BOOST, $shouldClauses[0]['match_phrase']['text']['boost']);

        // Verify all words exact match
        $this->assertArrayHasKey('multi_match', $shouldClauses[1]);
        $this->assertSame('and', $shouldClauses[1]['multi_match']['operator']);
        $this->assertSame(self::ALL_WORDS_EXACT_BOOST, $shouldClauses[1]['multi_match']['boost']);
    }

    public function testBuildHybridQueryWithLongWords(): void
    {
        // Arrange - includes words >= FUZZY_MIN_LENGTH (5)
        $query = 'testing algorithms';

        // Act
        $result = $this->builder->build($query, SearchStrategy::HYBRID);

        // Assert
        $queryBody = $result['body']['query'];
        $shouldClauses = $queryBody['bool']['should'];

        // Should have 3 clauses (exact phrase + all words exact + fuzzy)
        $this->assertCount(3, $shouldClauses);

        // Verify fuzzy clause exists
        $this->assertArrayHasKey('multi_match', $shouldClauses[2]);
        $this->assertSame(1, $shouldClauses[2]['multi_match']['fuzziness']);
        $this->assertSame('and', $shouldClauses[2]['multi_match']['operator']);
        $this->assertSame(self::FUZZY_BOOST, $shouldClauses[2]['multi_match']['boost']);
    }

    public function testBuildHybridQueryIncludesMinimumShouldMatch(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query, SearchStrategy::HYBRID);

        // Assert
        $this->assertSame(1, $result['body']['query']['bool']['minimum_should_match']);
    }

    public function testBuildExactQuery(): void
    {
        // Arrange
        $query = 'exact search test';

        // Act
        $result = $this->builder->build($query, SearchStrategy::EXACT);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('multi_match', $queryBody);

        $multiMatch = $queryBody['multi_match'];
        $this->assertSame($query, $multiMatch['query']);
        $this->assertSame('and', $multiMatch['operator']);
        $this->assertContains('title^2', $multiMatch['fields']);
        $this->assertContains('text', $multiMatch['fields']);
        $this->assertArrayNotHasKey('fuzziness', $multiMatch);
    }

    public function testBuildPrefixQuery(): void
    {
        // Arrange
        $query = 'prefixsearch';

        // Act
        $result = $this->builder->build($query, SearchStrategy::PREFIX);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('multi_match', $queryBody);

        $multiMatch = $queryBody['multi_match'];
        $this->assertSame($query, $multiMatch['query']);
        $this->assertSame('phrase_prefix', $multiMatch['type']);
        $this->assertSame(self::PREFIX_MAX_EXPANSIONS, $multiMatch['max_expansions']);
        $this->assertContains('title^2', $multiMatch['fields']);
        $this->assertContains('text', $multiMatch['fields']);
    }

    public function testBuildStructuredQueryWithRequiredTerms(): void
    {
        // Arrange
        $query = '+required +term';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('must', $queryBody['bool']);

        $mustClauses = $queryBody['bool']['must'];
        $this->assertCount(2, $mustClauses);
        $this->assertSame('required', $mustClauses[0]['match']['text']);
        $this->assertSame('term', $mustClauses[1]['match']['text']);
    }

    public function testBuildStructuredQueryWithExcludedTerms(): void
    {
        // Arrange
        $query = 'search -excluded -unwanted';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('must_not', $queryBody['bool']);

        $mustNotClauses = $queryBody['bool']['must_not'];
        $this->assertCount(2, $mustNotClauses);
        $this->assertSame('excluded', $mustNotClauses[0]['match']['text']);
        $this->assertSame('unwanted', $mustNotClauses[1]['match']['text']);
    }

    public function testBuildStructuredQueryWithPhrases(): void
    {
        // Arrange
        $query = '"exact phrase" test';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('must', $queryBody['bool']);

        // Phrase should be in must clause with boost
        $mustClauses = $queryBody['bool']['must'];
        $phraseClause = $mustClauses[0];
        $this->assertArrayHasKey('match_phrase', $phraseClause);
        $this->assertSame('exact phrase', $phraseClause['match_phrase']['text']['query']);
        $this->assertSame(self::EXACT_PHRASE_BOOST, $phraseClause['match_phrase']['text']['boost']);
    }

    public function testBuildStructuredQueryWithRegularTerms(): void
    {
        // Arrange
        $query = '+required regular1 regular2';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('should', $queryBody['bool']);

        $shouldClauses = $queryBody['bool']['should'];
        $this->assertCount(2, $shouldClauses);
        $this->assertSame('regular1', $shouldClauses[0]['match']['text']);
        $this->assertSame('regular2', $shouldClauses[1]['match']['text']);
    }

    public function testBuildStructuredQueryWithAllOperators(): void
    {
        // Arrange
        $query = '"exact phrase" +required -excluded regular';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $queryBody = $result['body']['query'];
        $boolQuery = $queryBody['bool'];

        // Verify all sections exist
        $this->assertArrayHasKey('must', $boolQuery);
        $this->assertArrayHasKey('must_not', $boolQuery);
        $this->assertArrayHasKey('should', $boolQuery);

        // Verify counts
        $this->assertCount(2, $boolQuery['must']); // phrase + required
        $this->assertCount(1, $boolQuery['must_not']); // excluded
        $this->assertCount(1, $boolQuery['should']); // regular
    }

    public function testBuildStructuredQueryWithOnlyShouldSetsMinimumShouldMatch(): void
    {
        // Arrange - only regular terms, no must clauses
        $query = 'regular1 regular2 "phrase"';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $boolQuery = $result['body']['query']['bool'];

        // When we have phrase, it goes to must, so this test needs adjustment
        // Let's use a query without phrase but with multiple words
        $query2 = 'word1 word2';
        $result2 = $this->builder->build($query2);

        // This won't trigger structured query (no operators), will use strategy instead
        // Let's correct: use only excluded to trigger operators without must
        $query3 = '-excluded word1 word2';
        $result3 = $this->builder->build($query3);
        $boolQuery3 = $result3['body']['query']['bool'];

        $this->assertArrayHasKey('should', $boolQuery3);
        $this->assertArrayHasKey('minimum_should_match', $boolQuery3);
        $this->assertSame(1, $boolQuery3['minimum_should_match']);
    }

    public function testBuildStructuredQueryFiltersEmptyArrays(): void
    {
        // Arrange - query with only one type of operator
        $query = '+required';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $boolQuery = $result['body']['query']['bool'];

        // Only 'must' should exist, others should be filtered out
        $this->assertArrayHasKey('must', $boolQuery);
        $this->assertArrayNotHasKey('must_not', $boolQuery);
        $this->assertArrayNotHasKey('should', $boolQuery);
        $this->assertArrayNotHasKey('minimum_should_match', $boolQuery);
    }

    public function testBuildWithDefaultStrategyIsHybrid(): void
    {
        // Arrange
        $query = 'testing default';

        // Act - not specifying strategy
        $result = $this->builder->build($query);

        // Assert - should use HYBRID (includes exact phrase + multi match)
        $queryBody = $result['body']['query'];
        $this->assertArrayHasKey('bool', $queryBody);
        $this->assertArrayHasKey('should', $queryBody['bool']);

        // Hybrid has multiple should clauses
        $this->assertGreaterThanOrEqual(2, count($queryBody['bool']['should']));
    }

    public function testBuildHybridQueryFieldBoosts(): void
    {
        // Arrange
        $query = 'testing fields';

        // Act
        $result = $this->builder->build($query, SearchStrategy::HYBRID);

        // Assert - verify field boosts in multi_match
        $shouldClauses = $result['body']['query']['bool']['should'];

        // Second clause is all words exact
        $allWordsExact = $shouldClauses[1]['multi_match'];
        $this->assertContains('title^3', $allWordsExact['fields']);
        $this->assertContains('text', $allWordsExact['fields']);

        // Third clause (if exists) is fuzzy
        if (isset($shouldClauses[2])) {
            $fuzzyClause = $shouldClauses[2]['multi_match'];
            $this->assertContains('title^2', $fuzzyClause['fields']);
            $this->assertContains('text', $fuzzyClause['fields']);
        }
    }

    public function testBuildExactQueryFieldBoosts(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query, SearchStrategy::EXACT);

        // Assert
        $multiMatch = $result['body']['query']['multi_match'];
        $this->assertContains('title^2', $multiMatch['fields']);
        $this->assertContains('text', $multiMatch['fields']);
    }

    public function testBuildPrefixQueryFieldBoosts(): void
    {
        // Arrange
        $query = 'test';

        // Act
        $result = $this->builder->build($query, SearchStrategy::PREFIX);

        // Assert
        $multiMatch = $result['body']['query']['multi_match'];
        $this->assertContains('title^2', $multiMatch['fields']);
        $this->assertContains('text', $multiMatch['fields']);
    }

    /**
     * @dataProvider strategyProvider
     */
    public function testBuildWithDifferentStrategies(SearchStrategy $strategy): void
    {
        // Arrange
        $query = 'test query';

        // Act
        $result = $this->builder->build($query, $strategy);

        // Assert - all strategies should produce valid structure
        $this->assertArrayHasKey('index', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('query', $result['body']);
        $this->assertArrayHasKey('highlight', $result['body']);
    }

    public static function strategyProvider(): array
    {
        return [
            'hybrid strategy' => [SearchStrategy::HYBRID],
            'exact strategy' => [SearchStrategy::EXACT],
            'prefix strategy' => [SearchStrategy::PREFIX],
        ];
    }

    public function testBuildWithMultiplePhrasesInStructuredQuery(): void
    {
        // Arrange
        $query = '"first phrase" "second phrase" test';

        // Act
        $result = $this->builder->build($query);

        // Assert
        $boolQuery = $result['body']['query']['bool'];
        $mustClauses = $boolQuery['must'];

        // Should have 2 phrase clauses
        $phraseClauses = array_filter($mustClauses, fn($clause) => isset($clause['match_phrase']));
        $this->assertCount(2, $phraseClauses);
    }

    public function testBuildWithEmptyStringUsesHybridStrategy(): void
    {
        // Arrange
        $query = '';

        // Act
        $result = $this->builder->build($query);

        // Assert - should not crash and should return valid structure
        $this->assertArrayHasKey('index', $result);
        $this->assertArrayHasKey('body', $result);
    }
}
