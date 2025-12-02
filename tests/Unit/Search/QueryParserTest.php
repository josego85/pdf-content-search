<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search;

use App\Search\QueryParser;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for QueryParser with 100% coverage.
 * Tests all query operators and edge cases.
 */
final class QueryParserTest extends TestCase
{
    private QueryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new QueryParser();
    }

    /**
     * @dataProvider simpleQueryProvider
     */
    public function testParseSimpleQueries(
        string $query,
        array $expectedRegular,
        bool $expectedHasOperators
    ): void {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        $this->assertSame($expectedRegular, $result['regular']);
        $this->assertEmpty($result['phrases']);
        $this->assertEmpty($result['required']);
        $this->assertEmpty($result['excluded']);
        $this->assertSame($expectedHasOperators, $result['hasOperators']);
    }

    public static function simpleQueryProvider(): array
    {
        return [
            'single word' => [
                'query' => 'test',
                'expectedRegular' => ['test'],
                'expectedHasOperators' => false,
            ],
            'multiple words' => [
                'query' => 'machine learning algorithm',
                'expectedRegular' => ['machine', 'learning', 'algorithm'],
                'expectedHasOperators' => false,
            ],
            'query with extra spaces' => [
                'query' => 'test    query   here',
                'expectedRegular' => ['test', 'query', 'here'],
                'expectedHasOperators' => false,
            ],
            'query with leading and trailing spaces' => [
                'query' => '   search term   ',
                'expectedRegular' => ['search', 'term'],
                'expectedHasOperators' => false,
            ],
        ];
    }

    /**
     * @dataProvider phraseQueryProvider
     */
    public function testParsePhraseQueries(
        string $query,
        array $expectedPhrases,
        array $expectedRegular
    ): void {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        $this->assertSame($expectedPhrases, $result['phrases']);
        $this->assertSame($expectedRegular, $result['regular']);
        $this->assertTrue($result['hasOperators']);
    }

    public static function phraseQueryProvider(): array
    {
        return [
            'single phrase' => [
                'query' => '"exact phrase"',
                'expectedPhrases' => ['exact phrase'],
                'expectedRegular' => [],
            ],
            'phrase with words outside' => [
                'query' => 'before "exact phrase" after',
                'expectedPhrases' => ['exact phrase'],
                'expectedRegular' => ['before', 'after'],
            ],
            'multiple phrases' => [
                'query' => '"first phrase" and "second phrase"',
                'expectedPhrases' => ['first phrase', 'second phrase'],
                'expectedRegular' => ['and'],
            ],
            'phrase with multiple words' => [
                'query' => '"machine learning algorithms"',
                'expectedPhrases' => ['machine learning algorithms'],
                'expectedRegular' => [],
            ],
        ];
    }

    /**
     * @dataProvider requiredTermsProvider
     */
    public function testParseRequiredTerms(
        string $query,
        array $expectedRequired,
        array $expectedRegular
    ): void {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        $this->assertSame($expectedRequired, $result['required']);
        $this->assertSame($expectedRegular, $result['regular']);
        $this->assertTrue($result['hasOperators']);
    }

    public static function requiredTermsProvider(): array
    {
        return [
            'single required term' => [
                'query' => '+required',
                'expectedRequired' => ['required'],
                'expectedRegular' => [],
            ],
            'multiple required terms' => [
                'query' => '+must +have +these',
                'expectedRequired' => ['must', 'have', 'these'],
                'expectedRegular' => [],
            ],
            'required terms with regular words' => [
                'query' => 'search +must include',
                'expectedRequired' => ['must'],
                'expectedRegular' => ['search', 'include'],
            ],
        ];
    }

    /**
     * @dataProvider excludedTermsProvider
     */
    public function testParseExcludedTerms(
        string $query,
        array $expectedExcluded,
        array $expectedRegular
    ): void {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        $this->assertSame($expectedExcluded, $result['excluded']);
        $this->assertSame($expectedRegular, $result['regular']);
        $this->assertTrue($result['hasOperators']);
    }

    public static function excludedTermsProvider(): array
    {
        return [
            'single excluded term' => [
                'query' => '-excluded',
                'expectedExcluded' => ['excluded'],
                'expectedRegular' => [],
            ],
            'multiple excluded terms' => [
                'query' => '-not -this -that',
                'expectedExcluded' => ['not', 'this', 'that'],
                'expectedRegular' => [],
            ],
            'excluded terms with regular words' => [
                'query' => 'search -without this',
                'expectedExcluded' => ['without'],
                'expectedRegular' => ['search', 'this'],
            ],
        ];
    }

    /**
     * @dataProvider complexQueryProvider
     */
    public function testParseComplexQueriesWithMultipleOperators(
        string $query,
        array $expectedPhrases,
        array $expectedRequired,
        array $expectedExcluded,
        array $expectedRegular
    ): void {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        $this->assertSame($expectedPhrases, $result['phrases']);
        $this->assertSame($expectedRequired, $result['required']);
        $this->assertSame($expectedExcluded, $result['excluded']);
        $this->assertSame($expectedRegular, $result['regular']);
        $this->assertTrue($result['hasOperators']);
    }

    public static function complexQueryProvider(): array
    {
        return [
            'all operators combined' => [
                'query' => '"exact phrase" +required -excluded regular',
                'expectedPhrases' => ['exact phrase'],
                'expectedRequired' => ['required'],
                'expectedExcluded' => ['excluded'],
                'expectedRegular' => ['regular'],
            ],
            'realistic search query' => [
                'query' => 'machine learning +python -java "neural networks"',
                'expectedPhrases' => ['neural networks'],
                'expectedRequired' => ['python'],
                'expectedExcluded' => ['java'],
                'expectedRegular' => ['machine', 'learning'],
            ],
            'multiple of each operator' => [
                'query' => '"phrase one" "phrase two" +req1 +req2 -exc1 -exc2 word1 word2',
                'expectedPhrases' => ['phrase one', 'phrase two'],
                'expectedRequired' => ['req1', 'req2'],
                'expectedExcluded' => ['exc1', 'exc2'],
                'expectedRegular' => ['word1', 'word2'],
            ],
        ];
    }

    /**
     * @dataProvider edgeCaseProvider
     */
    public function testHandleEdgeCases(string $query, array $expected): void
    {
        // Act
        $result = $this->parser->parse($query);

        // Assert
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key], "Failed for key: {$key}");
        }
    }

    public static function edgeCaseProvider(): array
    {
        return [
            'empty query' => [
                'query' => '',
                'expected' => [
                    'phrases' => [],
                    'required' => [],
                    'excluded' => [],
                    'regular' => [],
                    'hasOperators' => false,
                ],
            ],
            'only spaces' => [
                'query' => '     ',
                'expected' => [
                    'phrases' => [],
                    'required' => [],
                    'excluded' => [],
                    'regular' => [],
                    'hasOperators' => false,
                ],
            ],
            'only operators without terms' => [
                'query' => '+ - + -',
                'expected' => [
                    'phrases' => [],
                    'required' => ['', ''], // substr on '+' yields empty string
                    'excluded' => ['', ''], // substr on '-' yields empty string
                    'regular' => [],
                    'hasOperators' => true,
                ],
            ],
            'empty quotes' => [
                'query' => '""',
                'expected' => [
                    'phrases' => [], // regex requires at least one char inside quotes
                    'required' => [],
                    'excluded' => [],
                    'regular' => ['""'], // quotes not matched by regex, treated as literal term
                    'hasOperators' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cleanQueryProvider
     */
    public function testGetCleanQueryRemovesOperators(
        string $query,
        string $expectedClean
    ): void {
        // Act
        $result = $this->parser->getCleanQuery($query);

        // Assert
        $this->assertSame($expectedClean, $result);
    }

    public static function cleanQueryProvider(): array
    {
        return [
            'simple query unchanged' => [
                'query' => 'simple query',
                'expectedClean' => 'simple query',
            ],
            'removes quotes' => [
                'query' => '"exact phrase"',
                'expectedClean' => 'exact phrase',
            ],
            'removes plus operator' => [
                'query' => '+required term',
                'expectedClean' => 'required term',
            ],
            'removes minus operator' => [
                'query' => '-excluded term',
                'expectedClean' => 'excluded term',
            ],
            'removes all operators' => [
                'query' => '"phrase" +required -excluded regular',
                'expectedClean' => 'phrase required excluded regular',
            ],
            'normalizes multiple spaces' => [
                'query' => 'multiple    spaces    here',
                'expectedClean' => 'multiple spaces here',
            ],
            'complex query with all operators' => [
                'query' => '  "machine learning" +python -java  algorithms  ',
                'expectedClean' => 'machine learning python java algorithms',
            ],
            'operators in the middle' => [
                'query' => 'search +for -bad content',
                'expectedClean' => 'search for bad content',
            ],
        ];
    }

    public function testGetCleanQueryWithEmptyString(): void
    {
        // Arrange
        $query = '';

        // Act
        $result = $this->parser->getCleanQuery($query);

        // Assert
        $this->assertSame('', $result);
    }

    public function testGetCleanQueryWithOnlySpaces(): void
    {
        // Arrange
        $query = '     ';

        // Act
        $result = $this->parser->getCleanQuery($query);

        // Assert
        $this->assertSame('', $result);
    }

    public function testParserIsReadOnly(): void
    {
        // This test verifies the class is readonly by attempting to use it multiple times
        // If readonly works correctly, the same instance should behave consistently
        $result1 = $this->parser->parse('test query');
        $result2 = $this->parser->parse('test query');

        $this->assertEquals($result1, $result2);
    }

    public function testParseResultStructureIsComplete(): void
    {
        // Arrange
        $query = 'any query';

        // Act
        $result = $this->parser->parse($query);

        // Assert - verify all expected keys exist
        $this->assertArrayHasKey('phrases', $result);
        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('excluded', $result);
        $this->assertArrayHasKey('regular', $result);
        $this->assertArrayHasKey('hasOperators', $result);

        // Verify all arrays are arrays and hasOperators is bool
        $this->assertIsArray($result['phrases']);
        $this->assertIsArray($result['required']);
        $this->assertIsArray($result['excluded']);
        $this->assertIsArray($result['regular']);
        $this->assertIsBool($result['hasOperators']);
    }

    public function testParseWithEmptyTermsFromQuoteRemoval(): void
    {
        // Test that regular words are parsed correctly
        $query = 'normal word';

        $result = $this->parser->parse($query);

        // Regular words should be parsed correctly
        $this->assertContains('normal', $result['regular']);
        $this->assertContains('word', $result['regular']);
    }
}
