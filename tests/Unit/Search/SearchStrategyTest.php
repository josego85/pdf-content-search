<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search;

use App\Search\SearchStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SearchStrategy enum.
 * Ensures all strategy cases are properly defined with correct values.
 */
final class SearchStrategyTest extends TestCase
{
    private const STRATEGY_HYBRID_VALUE = 'hybrid';
    private const STRATEGY_EXACT_VALUE = 'exact';
    private const STRATEGY_PREFIX_VALUE = 'prefix';
    private const STRATEGY_SEMANTIC_VALUE = 'semantic';
    private const STRATEGY_HYBRID_AI_VALUE = 'hybrid_ai';

    public function testHybridStrategyHasCorrectValue(): void
    {
        $this->assertSame(self::STRATEGY_HYBRID_VALUE, SearchStrategy::HYBRID->value);
    }

    public function testExactStrategyHasCorrectValue(): void
    {
        $this->assertSame(self::STRATEGY_EXACT_VALUE, SearchStrategy::EXACT->value);
    }

    public function testPrefixStrategyHasCorrectValue(): void
    {
        $this->assertSame(self::STRATEGY_PREFIX_VALUE, SearchStrategy::PREFIX->value);
    }

    public function testSemanticStrategyHasCorrectValue(): void
    {
        $this->assertSame(self::STRATEGY_SEMANTIC_VALUE, SearchStrategy::SEMANTIC->value);
    }

    public function testHybridAiStrategyHasCorrectValue(): void
    {
        $this->assertSame(self::STRATEGY_HYBRID_AI_VALUE, SearchStrategy::HYBRID_AI->value);
    }

    public function testAllStrategyValuesAreUnique(): void
    {
        $values = array_map(static fn (SearchStrategy $case) => $case->value, SearchStrategy::cases());

        $this->assertCount(5, $values, 'Expected exactly 5 strategy cases');
        $this->assertCount(5, array_unique($values), 'All strategy values must be unique');
    }

    public function testCanCreateStrategyFromString(): void
    {
        $hybrid = SearchStrategy::from(self::STRATEGY_HYBRID_VALUE);
        $exact = SearchStrategy::from(self::STRATEGY_EXACT_VALUE);
        $prefix = SearchStrategy::from(self::STRATEGY_PREFIX_VALUE);

        $this->assertSame(SearchStrategy::HYBRID, $hybrid);
        $this->assertSame(SearchStrategy::EXACT, $exact);
        $this->assertSame(SearchStrategy::PREFIX, $prefix);
    }

    public function testTryFromReturnsNullForInvalidStrategy(): void
    {
        $result = SearchStrategy::tryFrom('invalid');

        $this->assertNull($result);
    }

    public function testTryFromReturnsStrategyForValidValue(): void
    {
        $result = SearchStrategy::tryFrom(self::STRATEGY_HYBRID_VALUE);

        $this->assertInstanceOf(SearchStrategy::class, $result);
        $this->assertSame(SearchStrategy::HYBRID, $result);
    }

    /**
     * @dataProvider strategyProvider
     */
    public function testStrategyCanBeUsedInComparisons(SearchStrategy $strategy, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $strategy->value);
        $this->assertSame($strategy, SearchStrategy::from($expectedValue));
    }

    public static function strategyProvider(): array
    {
        return [
            'hybrid strategy' => [SearchStrategy::HYBRID, self::STRATEGY_HYBRID_VALUE],
            'exact strategy' => [SearchStrategy::EXACT, self::STRATEGY_EXACT_VALUE],
            'prefix strategy' => [SearchStrategy::PREFIX, self::STRATEGY_PREFIX_VALUE],
            'semantic strategy' => [SearchStrategy::SEMANTIC, self::STRATEGY_SEMANTIC_VALUE],
            'hybrid_ai strategy' => [SearchStrategy::HYBRID_AI, self::STRATEGY_HYBRID_AI_VALUE],
        ];
    }

    public function testAllCasesReturnsFiveStrategies(): void
    {
        $cases = SearchStrategy::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(SearchStrategy::HYBRID, $cases);
        $this->assertContains(SearchStrategy::EXACT, $cases);
        $this->assertContains(SearchStrategy::PREFIX, $cases);
        $this->assertContains(SearchStrategy::SEMANTIC, $cases);
        $this->assertContains(SearchStrategy::HYBRID_AI, $cases);
    }

    public function testStrategiesAreBackedByStrings(): void
    {
        foreach (SearchStrategy::cases() as $strategy) {
            $this->assertIsString($strategy->value);
            $this->assertNotEmpty($strategy->value);
        }
    }

    public function testStrategyNamesMatchExpectedConvention(): void
    {
        $this->assertSame('HYBRID', SearchStrategy::HYBRID->name);
        $this->assertSame('EXACT', SearchStrategy::EXACT->name);
        $this->assertSame('PREFIX', SearchStrategy::PREFIX->name);
    }
}
