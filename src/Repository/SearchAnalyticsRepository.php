<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SearchAnalytics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SearchAnalyticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchAnalytics::class);
    }

    /**
     * Get overview metrics for dashboard.
     */
    public function getOverviewMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('sa');

        return $qb
            ->select([
                'COUNT(sa.id) as totalSearches',
                'COUNT(DISTINCT sa.sessionId) as uniqueSessions',
                'AVG(sa.responseTimeMs) as avgResponseTime',
                'SUM(CASE WHEN sa.resultsCount = 0 THEN 1 ELSE 0 END) as zeroResultSearches',
                'SUM(CASE WHEN sa.clicked = true THEN 1 ELSE 0 END) as clickedSearches',
            ])
            ->where('sa.createdAt >= :startDate')
            ->andWhere('sa.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Get top queries by search count.
     */
    public function getTopQueries(\DateTime $startDate, \DateTime $endDate, int $limit = 20): array
    {
        return $this->createQueryBuilder('sa')
            ->select([
                'sa.query',
                'COUNT(sa.id) as searchCount',
                'AVG(sa.resultsCount) as avgResults',
                'SUM(CASE WHEN sa.clicked = true THEN 1 ELSE 0 END) as clicks',
            ])
            ->where('sa.createdAt >= :startDate')
            ->andWhere('sa.createdAt <= :endDate')
            ->groupBy('sa.query')
            ->orderBy('searchCount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get search volume trends (daily aggregation).
     */
    public function getSearchTrends(\DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                DATE(created_at) as date,
                COUNT(id) as "searchCount",
                search_strategy as "searchStrategy"
            FROM search_analytics
            WHERE created_at >= :startDate
              AND created_at <= :endDate
            GROUP BY DATE(created_at), search_strategy
            ORDER BY DATE(created_at) ASC
        ';

        $result = $conn->executeQuery($sql, [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Get click position distribution.
     */
    public function getClickPositionDistribution(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('sa')
            ->select([
                'sa.clickedPosition as position',
                'COUNT(sa.id) as clicks',
            ])
            ->where('sa.clicked = true')
            ->andWhere('sa.createdAt >= :startDate')
            ->andWhere('sa.createdAt <= :endDate')
            ->groupBy('sa.clickedPosition')
            ->orderBy('sa.clickedPosition', 'ASC')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get zero-result queries (queries that need content).
     */
    public function getZeroResultQueries(\DateTime $startDate, \DateTime $endDate, int $limit = 20): array
    {
        return $this->createQueryBuilder('sa')
            ->select([
                'sa.query',
                'COUNT(sa.id) as searchCount',
            ])
            ->where('sa.resultsCount = 0')
            ->andWhere('sa.createdAt >= :startDate')
            ->andWhere('sa.createdAt <= :endDate')
            ->groupBy('sa.query')
            ->orderBy('searchCount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
