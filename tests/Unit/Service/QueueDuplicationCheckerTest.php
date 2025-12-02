<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\QueueDuplicationChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class QueueDuplicationCheckerTest extends TestCase
{
    private CacheInterface $cache;

    private QueueDuplicationChecker $checker;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->checker = new QueueDuplicationChecker($this->cache, 300);
    }

    public function testMarkAsQueuedCreatesEntry(): void
    {
        $this->checker->markAsQueued('test.pdf', 1, 'es');

        self::assertTrue(
            $this->checker->isTranslationQueued('test.pdf', 1, 'es'),
            'Translation should be marked as queued'
        );
    }

    public function testIsTranslationQueuedReturnsFalseForNonExistent(): void
    {
        self::assertFalse(
            $this->checker->isTranslationQueued('test.pdf', 1, 'es'),
            'Non-existent translation should not be queued'
        );
    }

    public function testRemoveFromQueuedClearsEntry(): void
    {
        $this->checker->markAsQueued('test.pdf', 1, 'es');
        $this->checker->markAsProcessed('test.pdf', 1, 'es');

        self::assertFalse(
            $this->checker->isTranslationQueued('test.pdf', 1, 'es'),
            'Translation should be removed from queue'
        );
    }

    public function testDifferentFilesAreSeparate(): void
    {
        $this->checker->markAsQueued('file1.pdf', 1, 'es');
        $this->checker->markAsQueued('file2.pdf', 1, 'es');

        self::assertTrue($this->checker->isTranslationQueued('file1.pdf', 1, 'es'));
        self::assertTrue($this->checker->isTranslationQueued('file2.pdf', 1, 'es'));
    }

    public function testDifferentPagesAreSeparate(): void
    {
        $this->checker->markAsQueued('test.pdf', 1, 'es');
        $this->checker->markAsQueued('test.pdf', 2, 'es');

        self::assertTrue($this->checker->isTranslationQueued('test.pdf', 1, 'es'));
        self::assertTrue($this->checker->isTranslationQueued('test.pdf', 2, 'es'));
    }

    public function testDifferentLanguagesAreSeparate(): void
    {
        $this->checker->markAsQueued('test.pdf', 1, 'es');
        $this->checker->markAsQueued('test.pdf', 1, 'en');

        self::assertTrue($this->checker->isTranslationQueued('test.pdf', 1, 'es'));
        self::assertTrue($this->checker->isTranslationQueued('test.pdf', 1, 'en'));
    }

    public function testRemoveOnlyRemovesSpecificEntry(): void
    {
        $this->checker->markAsQueued('test.pdf', 1, 'es');
        $this->checker->markAsQueued('test.pdf', 2, 'es');

        $this->checker->markAsProcessed('test.pdf', 1, 'es');

        self::assertFalse($this->checker->isTranslationQueued('test.pdf', 1, 'es'));
        self::assertTrue($this->checker->isTranslationQueued('test.pdf', 2, 'es'));
    }
}
