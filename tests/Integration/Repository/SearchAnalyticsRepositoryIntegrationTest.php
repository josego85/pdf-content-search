<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\SearchAnalytics;
use App\Repository\SearchAnalyticsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for SearchAnalyticsRepository.
 * Tests real database queries with SQLite.
 */
final class SearchAnalyticsRepositoryIntegrationTest extends KernelTestCase
{
    private static bool $schemaCreated = false;

    private SearchAnalyticsRepository $repository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(SearchAnalytics::class);

        // Create schema only once
        if (!self::$schemaCreated) {
            $schemaTool = new SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

            // Drop and recreate schema
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);

            self::$schemaCreated = true;
        }

        // Clean data before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\SearchAnalytics')->execute();
        $this->entityManager->clear();
    }

    public function testGetOverviewMetricsWithNoData(): void
    {
        $startDate = new \DateTime('-7 days');
        $endDate = new \DateTime();

        $metrics = $this->repository->getOverviewMetrics($startDate, $endDate);

        $this->assertSame(0, (int) $metrics['totalSearches']);
        $this->assertSame(0, (int) $metrics['uniqueSessions']);
    }

    public function testGetOverviewMetricsWithRealData(): void
    {
        $this->createSearchAnalytics('java', 'session-1', 10, 100);
        $this->createSearchAnalytics('python', 'session-2', 5, 150, true);
        $this->createSearchAnalytics('php', 'session-1', 0, 80);

        $metrics = $this->repository->getOverviewMetrics(
            new \DateTime('-1 day'),
            new \DateTime('+1 day')
        );

        $this->assertSame(3, (int) $metrics['totalSearches']);
        $this->assertSame(2, (int) $metrics['uniqueSessions']);
        $this->assertSame(1, (int) $metrics['zeroResultSearches']);
        $this->assertSame(1, (int) $metrics['clickedSearches']);
    }

    public function testGetTopQueriesOrdersBySearchCount(): void
    {
        $this->createSearchAnalytics('java', 'session-1', 10, 100);
        $this->createSearchAnalytics('java', 'session-2', 12, 110);
        $this->createSearchAnalytics('python', 'session-1', 8, 95);

        $result = $this->repository->getTopQueries(
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            10
        );

        $this->assertCount(2, $result);
        $this->assertSame('java', $result[0]['query']);
        $this->assertSame(2, (int) $result[0]['searchCount']);
    }

    public function testGetTopQueriesRespectsLimit(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->createSearchAnalytics("query-{$i}", "session-{$i}", $i, 100);
        }

        $result = $this->repository->getTopQueries(
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            3
        );

        $this->assertLessThanOrEqual(3, count($result));
    }

    public function testGetSearchTrendsReturnsArray(): void
    {
        $this->createSearchAnalytics('test', 'session-1', 10, 100);

        $result = $this->repository->getSearchTrends(
            new \DateTime('-2 days'),
            new \DateTime('+1 day')
        );

        $this->assertIsArray($result);
    }

    public function testGetClickPositionDistribution(): void
    {
        $this->createSearchAnalyticsWithClickPosition('test1', 1);
        $this->createSearchAnalyticsWithClickPosition('test2', 2);

        $result = $this->repository->getClickPositionDistribution(
            new \DateTime('-1 day'),
            new \DateTime('+1 day')
        );

        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testGetZeroResultQueries(): void
    {
        $this->createSearchAnalytics('nonexistent', 'session-1', 0, 50);
        $this->createSearchAnalytics('found', 'session-2', 10, 100);

        $result = $this->repository->getZeroResultQueries(
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            10
        );

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertSame('nonexistent', $result[0]['query']);
    }

    public function testSaveMethodPersistsEntity(): void
    {
        $entity = new SearchAnalytics();
        $entity->setSessionId('save-test-' . time());
        $entity->setQuery('save test');
        $entity->setSearchStrategy('hybrid_ai');
        $entity->setResultsCount(5);
        $entity->setResponseTimeMs(100);

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());
    }

    private function createSearchAnalytics(
        string $query,
        string $sessionId,
        int $resultsCount,
        int $responseTime,
        bool $clicked = false
    ): void {
        $analytics = new SearchAnalytics();
        $analytics->setSessionId($sessionId);
        $analytics->setQuery($query);
        $analytics->setSearchStrategy('hybrid_ai');
        $analytics->setResultsCount($resultsCount);
        $analytics->setResponseTimeMs($responseTime);
        $analytics->setClicked($clicked);

        $this->entityManager->persist($analytics);
        $this->entityManager->flush();
    }

    private function createSearchAnalyticsWithClickPosition(string $query, int $position): void
    {
        $analytics = new SearchAnalytics();
        $analytics->setSessionId(uniqid('click-'));
        $analytics->setQuery($query);
        $analytics->setSearchStrategy('hybrid_ai');
        $analytics->setResultsCount(10);
        $analytics->setResponseTimeMs(100);
        $analytics->setClicked(true);
        $analytics->setClickedPosition($position);

        $this->entityManager->persist($analytics);
        $this->entityManager->flush();
    }
}
