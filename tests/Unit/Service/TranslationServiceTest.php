<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\PdfPageTranslation;
use App\Service\LanguageDetector;
use App\Service\OllamaService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class TranslationServiceTest extends TestCase
{
    private TranslationService $service;

    private EntityManagerInterface $entityManager;

    private CacheItemPoolInterface $cache;

    private OllamaService $ollamaService;

    private LanguageDetector $languageDetector;

    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->ollamaService = $this->createMock(OllamaService::class);
        $this->languageDetector = $this->createMock(LanguageDetector::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->service = new TranslationService(
            $this->entityManager,
            $this->cache,
            $this->ollamaService,
            $this->languageDetector,
            3600
        );
    }

    public function testFindExistingTranslationReturnsFromCache(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->method('get')
            ->willReturn('Cached translation');

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $result = $this->service->findExistingTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertIsArray($result);
        self::assertSame('Cached translation', $result['text']);
        self::assertSame('cache', $result['source']);
        self::assertSame('en', $result['source_language']);
        self::assertTrue($result['cached']);
    }

    public function testFindExistingTranslationReturnsFromDatabase(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false);

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $translation = new PdfPageTranslation();
        $translation->setTranslatedText('Database translation');

        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'pdfFilename' => 'test.pdf',
                'pageNumber' => 1,
                'targetLanguage' => 'es',
            ])
            ->willReturn($translation);

        $this->cache
            ->expects(self::once())
            ->method('save');

        $result = $this->service->findExistingTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertIsArray($result);
        self::assertSame('Database translation', $result['text']);
        self::assertSame('database', $result['source']);
        self::assertSame('en', $result['source_language']);
        self::assertFalse($result['cached']);
    }

    public function testFindExistingTranslationReturnsNullWhenNotFound(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false);

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->service->findExistingTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertNull($result);
    }

    public function testFindExistingTranslationReturnsOriginalWhenSameLanguage(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'es']);

        $result = $this->service->findExistingTranslation(
            'test.pdf',
            1,
            'Texto original',
            'es'
        );

        self::assertIsArray($result);
        self::assertSame('Texto original', $result['text']);
        self::assertSame('original', $result['source']);
        self::assertSame('es', $result['source_language']);
        self::assertFalse($result['cached']);
    }

    public function testGetTranslationReturnsFromCache(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->method('get')
            ->willReturn('Cached translation');

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $result = $this->service->getTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertSame('Cached translation', $result['text']);
        self::assertSame('cache', $result['source']);
        self::assertTrue($result['cached']);
    }

    public function testGetTranslationGeneratesNewTranslation(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->method('set')
            ->willReturn($cacheItem);
        $cacheItem
            ->method('expiresAfter')
            ->willReturn($cacheItem);

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $this->ollamaService
            ->expects(self::once())
            ->method('translate')
            ->with('Original text', 'es')
            ->willReturn('Nueva traducción');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(PdfPageTranslation::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->cache
            ->expects(self::once())
            ->method('save');

        $result = $this->service->getTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertSame('Nueva traducción', $result['text']);
        self::assertSame('translated', $result['source']);
        self::assertSame('en', $result['source_language']);
        self::assertFalse($result['cached']);
    }

    public function testGetTranslationReturnsOriginalWhenSameLanguage(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'fr']);

        $result = $this->service->getTranslation(
            'test.pdf',
            1,
            'Texte français',
            'fr'
        );

        self::assertSame('Texte français', $result['text']);
        self::assertSame('original', $result['source']);
    }

    public function testGetTranslationHandlesEmptyText(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->method('set')
            ->willReturn($cacheItem);
        $cacheItem
            ->method('expiresAfter')
            ->willReturn($cacheItem);

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $this->ollamaService
            ->method('translate')
            ->with('', 'es')
            ->willReturn('');

        $result = $this->service->getTranslation(
            'test.pdf',
            1,
            '',
            'es'
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('text', $result);
        self::assertSame('', $result['text']);
    }

    public function testFindExistingTranslationStoresInCacheAfterDatabaseLookup(): void
    {
        $this->languageDetector
            ->method('detect')
            ->willReturn(['language' => 'en']);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->expects(self::once())
            ->method('set')
            ->with('DB Translation')
            ->willReturn($cacheItem);
        $cacheItem
            ->expects(self::once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturn($cacheItem);

        $this->cache
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $translation = new PdfPageTranslation();
        $translation->setTranslatedText('DB Translation');

        $this->repository
            ->method('findOneBy')
            ->willReturn($translation);

        $result = $this->service->findExistingTranslation(
            'test.pdf',
            1,
            'Original text',
            'es'
        );

        self::assertSame('DB Translation', $result['text']);
        self::assertSame('database', $result['source']);
        self::assertFalse($result['cached']);
    }
}
