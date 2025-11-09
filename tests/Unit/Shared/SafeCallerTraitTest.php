<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Traits\SafeCallerTrait;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SafeCallerTrait.
 * Ensures proper exception handling and error message wrapping.
 */
final class SafeCallerTraitTest extends TestCase
{
    private const ERROR_MESSAGE = 'Operation failed';
    private const ELASTICSEARCH_ERROR = 'Elasticsearch connection error';

    private SafeCallerTraitTestSubject $subject;

    protected function setUp(): void
    {
        $this->subject = new SafeCallerTraitTestSubject();
    }

    public function testSafeCallReturnsResultWhenCallableSucceeds(): void
    {
        // Arrange
        $expectedResult = 'success';
        $callable = static fn () => $expectedResult;

        // Act
        $result = $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    public function testSafeCallReturnsArrayWhenCallableReturnsArray(): void
    {
        // Arrange
        $expectedResult = ['key' => 'value', 'number' => 42];
        $callable = static fn () => $expectedResult;

        // Act
        $result = $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    public function testSafeCallReturnsNullWhenCallableReturnsNull(): void
    {
        // Arrange
        $callable = static fn () => null;

        // Act
        $result = $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);

        // Assert
        $this->assertNull($result);
    }

    public function testSafeCallThrowsRuntimeExceptionWhenElasticsearchExceptionOccurs(): void
    {
        // Arrange
        $elasticsearchException = new TestElasticsearchException(self::ELASTICSEARCH_ERROR);
        $callable = static function () use ($elasticsearchException) {
            throw $elasticsearchException;
        };

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(self::ERROR_MESSAGE . ': ' . self::ELASTICSEARCH_ERROR);

        $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);
    }

    public function testSafeCallWrapsElasticsearchExceptionAsPrevious(): void
    {
        // Arrange
        $elasticsearchException = new TestElasticsearchException(self::ELASTICSEARCH_ERROR);
        $callable = static function () use ($elasticsearchException) {
            throw $elasticsearchException;
        };

        // Act & Assert
        try {
            $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(ElasticsearchException::class, $e->getPrevious());
            $this->assertSame($elasticsearchException, $e->getPrevious());
        }
    }

    public function testSafeCallPreservesOriginalExceptionMessage(): void
    {
        // Arrange
        $originalMessage = 'Index not found';
        $elasticsearchException = new TestElasticsearchException($originalMessage);
        $callable = static function () use ($elasticsearchException) {
            throw $elasticsearchException;
        };

        // Act & Assert
        try {
            $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString($originalMessage, $e->getMessage());
            $this->assertStringContainsString(self::ERROR_MESSAGE, $e->getMessage());
        }
    }

    public function testSafeCallWithComplexOperation(): void
    {
        // Arrange
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        $callable = static function () use ($data) {
            $sum = array_sum($data);

            return $sum * 2;
        };

        // Act
        $result = $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);

        // Assert
        $this->assertSame(12, $result);
    }

    public function testSafeCallDoesNotCatchOtherExceptions(): void
    {
        // Arrange
        $callable = static function () {
            throw new \InvalidArgumentException('This is not an ElasticsearchException');
        };

        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This is not an ElasticsearchException');

        $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);
    }

    /**
     * @dataProvider returnValueProvider
     */
    public function testSafeCallHandlesDifferentReturnTypes(mixed $expectedValue): void
    {
        // Arrange
        $callable = static fn () => $expectedValue;

        // Act
        $result = $this->subject->executeSafeCall($callable, self::ERROR_MESSAGE);

        // Assert
        $this->assertSame($expectedValue, $result);
    }

    public static function returnValueProvider(): array
    {
        return [
            'string' => ['test string'],
            'integer' => [42],
            'float' => [3.14],
            'boolean true' => [true],
            'boolean false' => [false],
            'array' => [['a', 'b', 'c']],
            'null' => [null],
        ];
    }

    public function testSafeCallWithEmptyErrorMessage(): void
    {
        // Arrange
        $elasticsearchException = new TestElasticsearchException(self::ELASTICSEARCH_ERROR);
        $callable = static function () use ($elasticsearchException) {
            throw $elasticsearchException;
        };

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(': ' . self::ELASTICSEARCH_ERROR);

        $this->subject->executeSafeCall($callable, '');
    }
}

/**
 * Concrete class for testing SafeCallerTrait.
 * Traits cannot be instantiated directly, so we need a test subject.
 */
final class SafeCallerTraitTestSubject
{
    use SafeCallerTrait;

    public function executeSafeCall(callable $fn, string $errorMessage): mixed
    {
        return $this->safeCall($fn, $errorMessage);
    }
}

/**
 * Test implementation of ElasticsearchException.
 * The actual ElasticsearchException is an interface, so we need a concrete class for testing.
 */
final class TestElasticsearchException extends \Exception implements ElasticsearchException
{
}
