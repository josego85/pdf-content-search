# Analytics Dashboard - Implementation Plan

## 📋 Overview

Implementation of a custom analytics dashboard to track search behavior, user interactions, and content performance using PostgreSQL 16 + Vue.js 3 + ApexCharts.

**Branch:** `feature/analytics-dashboard`
**Estimated Time:** 15-21 hours (2-3 days)
**Stack:** Symfony 7.4, PostgreSQL 16, Vue.js 3, ApexCharts, Tailwind CSS

---

## 🎯 Goals

1. Track all search queries and user behavior
2. Provide actionable insights via dashboard
3. Measure search quality and content performance
4. Non-blocking async implementation
5. GDPR-compliant (IP anonymization, 90-day retention)

---

## 📊 Architecture

```
User Search Request
    ↓
SearchController → Response to User
    ↓
Async Event (Symfony Messenger)
    ↓
AnalyticsCollector → PostgreSQL (search_analytics table)
    ↓
AnalyticsController API ← Dashboard Vue.js Component
```

---

## 🗄️ Database Schema

### Table: `search_analytics`

```sql
CREATE TABLE search_analytics (
    id BIGSERIAL PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    query TEXT NOT NULL,
    search_strategy VARCHAR(20) NOT NULL DEFAULT 'hybrid_ai',
    results_count INT NOT NULL DEFAULT 0,
    response_time_ms INT NOT NULL,

    -- User interaction tracking
    clicked BOOLEAN DEFAULT FALSE,
    clicked_position INT,
    clicked_pdf VARCHAR(255),
    clicked_page INT,
    time_to_click_ms INT,

    -- Context
    user_ip VARCHAR(45),
    user_agent TEXT,
    referer TEXT,

    -- Timestamps
    created_at TIMESTAMP DEFAULT NOW(),

    -- Indexes
    INDEX idx_query (query(255)),
    INDEX idx_created_at (created_at),
    INDEX idx_session (session_id),
    INDEX idx_clicked (clicked)
);
```

### Table: `daily_analytics` (Aggregated metrics for performance)

```sql
CREATE TABLE daily_analytics (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    total_searches INT NOT NULL DEFAULT 0,
    unique_sessions INT NOT NULL DEFAULT 0,
    avg_response_time_ms INT NOT NULL DEFAULT 0,
    zero_result_searches INT NOT NULL DEFAULT 0,
    click_through_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    top_queries JSONB,
    created_at TIMESTAMP DEFAULT NOW(),

    INDEX idx_date (date)
);
```

---

## 📂 File Structure

```
src/
├── Command/
│   └── AggregateAnalyticsCommand.php        # Daily aggregation cron job
├── Controller/
│   └── AnalyticsController.php              # API endpoints
├── Entity/
│   ├── SearchAnalytics.php                  # Main analytics entity
│   └── DailyAnalytics.php                   # Aggregated metrics
├── Repository/
│   ├── SearchAnalyticsRepository.php        # Complex queries
│   └── DailyAnalyticsRepository.php
├── Service/
│   ├── AnalyticsService.php                 # Business logic
│   └── AnalyticsCollector.php               # Data collection
├── Message/
│   └── LogSearchAnalyticsMessage.php        # Async message
└── MessageHandler/
    └── LogSearchAnalyticsHandler.php        # Message handler

migrations/
└── VersionYYYYMMDDHHMMSS.php                # Migration for tables

assets/
├── components/
│   └── analytics/
│       ├── Dashboard.vue                    # Main dashboard
│       ├── KPICard.vue                      # Metric cards
│       ├── TopQueriesChart.vue              # Top queries table
│       ├── TrendsChart.vue                  # Line chart (time series)
│       ├── StrategyDistribution.vue         # Pie chart
│       ├── ClickPositionHeatmap.vue         # Bar chart
│       └── DateRangePicker.vue              # Filter component
└── pages/
    └── Analytics.vue                        # Page wrapper

templates/
└── analytics/
    └── index.html.twig                      # Entry point

config/
└── routes/
    └── analytics.yaml                       # Route definitions
```

---

## 🚀 Implementation Phases

### **Phase 1: Backend Foundation (4-6 hours)**

#### Step 1.1: Create Migration
**File:** `migrations/VersionYYYYMMDDHHMMSS.php`

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionYYYYMMDDHHMMSS extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create search_analytics and daily_analytics tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE search_analytics (
            id BIGSERIAL PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            query TEXT NOT NULL,
            search_strategy VARCHAR(20) NOT NULL DEFAULT \'hybrid_ai\',
            results_count INT NOT NULL DEFAULT 0,
            response_time_ms INT NOT NULL,
            clicked BOOLEAN DEFAULT FALSE,
            clicked_position INT,
            clicked_pdf VARCHAR(255),
            clicked_page INT,
            time_to_click_ms INT,
            user_ip VARCHAR(45),
            user_agent TEXT,
            referer TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        )');

        $this->addSql('CREATE INDEX idx_query ON search_analytics (query(255))');
        $this->addSql('CREATE INDEX idx_created_at ON search_analytics (created_at)');
        $this->addSql('CREATE INDEX idx_session ON search_analytics (session_id)');
        $this->addSql('CREATE INDEX idx_clicked ON search_analytics (clicked)');

        $this->addSql('CREATE TABLE daily_analytics (
            id SERIAL PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            total_searches INT NOT NULL DEFAULT 0,
            unique_sessions INT NOT NULL DEFAULT 0,
            avg_response_time_ms INT NOT NULL DEFAULT 0,
            zero_result_searches INT NOT NULL DEFAULT 0,
            click_through_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            top_queries JSONB,
            created_at TIMESTAMP DEFAULT NOW()
        )');

        $this->addSql('CREATE INDEX idx_date ON daily_analytics (date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS search_analytics');
        $this->addSql('DROP TABLE IF EXISTS daily_analytics');
    }
}
```

#### Step 1.2: Create Entities
**File:** `src/Entity/SearchAnalytics.php`

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SearchAnalyticsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchAnalyticsRepository::class)]
#[ORM\Table(name: 'search_analytics')]
#[ORM\Index(columns: ['query'], name: 'idx_query')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
#[ORM\Index(columns: ['session_id'], name: 'idx_session')]
#[ORM\Index(columns: ['clicked'], name: 'idx_clicked')]
class SearchAnalytics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $sessionId;

    #[ORM\Column(type: Types::TEXT)]
    private string $query;

    #[ORM\Column(length: 20)]
    private string $searchStrategy;

    #[ORM\Column]
    private int $resultsCount;

    #[ORM\Column]
    private int $responseTimeMs;

    #[ORM\Column]
    private bool $clicked = false;

    #[ORM\Column(nullable: true)]
    private ?int $clickedPosition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clickedPdf = null;

    #[ORM\Column(nullable: true)]
    private ?int $clickedPage = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeToClickMs = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $userIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $referer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function getSearchStrategy(): string
    {
        return $this->searchStrategy;
    }

    public function setSearchStrategy(string $searchStrategy): static
    {
        $this->searchStrategy = $searchStrategy;
        return $this;
    }

    public function getResultsCount(): int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): static
    {
        $this->resultsCount = $resultsCount;
        return $this;
    }

    public function getResponseTimeMs(): int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(int $responseTimeMs): static
    {
        $this->responseTimeMs = $responseTimeMs;
        return $this;
    }

    public function isClicked(): bool
    {
        return $this->clicked;
    }

    public function setClicked(bool $clicked): static
    {
        $this->clicked = $clicked;
        return $this;
    }

    public function getClickedPosition(): ?int
    {
        return $this->clickedPosition;
    }

    public function setClickedPosition(?int $clickedPosition): static
    {
        $this->clickedPosition = $clickedPosition;
        return $this;
    }

    public function getClickedPdf(): ?string
    {
        return $this->clickedPdf;
    }

    public function setClickedPdf(?string $clickedPdf): static
    {
        $this->clickedPdf = $clickedPdf;
        return $this;
    }

    public function getClickedPage(): ?int
    {
        return $this->clickedPage;
    }

    public function setClickedPage(?int $clickedPage): static
    {
        $this->clickedPage = $clickedPage;
        return $this;
    }

    public function getTimeToClickMs(): ?int
    {
        return $this->timeToClickMs;
    }

    public function setTimeToClickMs(?int $timeToClickMs): static
    {
        $this->timeToClickMs = $timeToClickMs;
        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserIp(?string $userIp): static
    {
        $this->userIp = $userIp;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): static
    {
        $this->referer = $referer;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
```

#### Step 1.3: Create Repository
**File:** `src/Repository/SearchAnalyticsRepository.php`

```php
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
     * Get overview metrics for dashboard
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
     * Get top queries by search count
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
     * Get search volume trends (daily aggregation)
     */
    public function getSearchTrends(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('sa')
            ->select([
                'DATE(sa.createdAt) as date',
                'COUNT(sa.id) as searchCount',
                'sa.searchStrategy',
            ])
            ->where('sa.createdAt >= :startDate')
            ->andWhere('sa.createdAt <= :endDate')
            ->groupBy('date', 'sa.searchStrategy')
            ->orderBy('date', 'ASC')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get click position distribution
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
     * Get zero-result queries (queries that need content)
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
```

#### Step 1.4: Create Async Message
**File:** `src/Message/LogSearchAnalyticsMessage.php`

```php
<?php

declare(strict_types=1);

namespace App\Message;

final class LogSearchAnalyticsMessage
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }
}
```

#### Step 1.5: Create Message Handler
**File:** `src/MessageHandler/LogSearchAnalyticsHandler.php`

```php
<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\SearchAnalytics;
use App\Message\LogSearchAnalyticsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class LogSearchAnalyticsHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(LogSearchAnalyticsMessage $message): void
    {
        $data = $message->getData();

        $analytics = new SearchAnalytics();
        $analytics->setSessionId($data['session_id']);
        $analytics->setQuery($data['query']);
        $analytics->setSearchStrategy($data['search_strategy'] ?? 'hybrid_ai');
        $analytics->setResultsCount($data['results_count']);
        $analytics->setResponseTimeMs($data['response_time_ms']);

        if (isset($data['user_ip'])) {
            $analytics->setUserIp($this->anonymizeIp($data['user_ip']));
        }

        if (isset($data['user_agent'])) {
            $analytics->setUserAgent($data['user_agent']);
        }

        if (isset($data['referer'])) {
            $analytics->setReferer($data['referer']);
        }

        $this->entityManager->persist($analytics);
        $this->entityManager->flush();
    }

    /**
     * Anonymize IP for GDPR compliance
     */
    private function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Keep first 3 octets, zero last octet
            return substr($ip, 0, strrpos($ip, '.')) . '.0';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Keep first 4 groups, zero the rest
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }

        return '0.0.0.0';
    }
}
```

#### Step 1.6: Create Analytics Service
**File:** `src/Service/AnalyticsCollector.php`

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Message\LogSearchAnalyticsMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

final class AnalyticsCollector
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * Log search analytics asynchronously
     */
    public function logSearch(
        Request $request,
        string $query,
        string $searchStrategy,
        int $resultsCount,
        int $responseTimeMs
    ): void {
        $data = [
            'session_id' => $request->getSession()->getId(),
            'query' => $query,
            'search_strategy' => $searchStrategy,
            'results_count' => $resultsCount,
            'response_time_ms' => $responseTimeMs,
            'user_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('Referer'),
        ];

        $this->messageBus->dispatch(new LogSearchAnalyticsMessage($data));
    }

    /**
     * Log click event (to be called from frontend)
     */
    public function logClick(
        string $sessionId,
        string $query,
        int $position,
        string $pdfFilename,
        int $pageNumber,
        int $timeToClickMs
    ): void {
        // This will be implemented in Phase 3 (frontend tracking)
        // For now, we focus on search logging
    }
}
```

#### Step 1.7: Integrate in SearchController
**File:** `src/Controller/SearchController.php` (modify existing)

```php
// Add to constructor:
public function __construct(
    private readonly SearchEngineInterface $searchEngine,
    private readonly QueryBuilderInterface $queryBuilder,
    private readonly RankFusionServiceInterface $rankFusion,
    private readonly AnalyticsCollector $analyticsCollector // NEW
) {
}

// In search() method, after building response:
public function search(Request $request): JsonResponse
{
    try {
        $startTime = microtime(true);
        $query = $request->query->get('q');

        if (empty($query)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Search query cannot be empty.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $strategyParam = $request->query->get('strategy', 'hybrid_ai');
        $strategy = $this->detectSearchStrategy($query, $strategyParam);
        $searchParams = $this->queryBuilder->build($query, $strategy);

        // Execute search (existing logic)
        if ($strategy === SearchStrategy::HYBRID_AI && isset($searchParams['lexical'], $searchParams['semantic'])) {
            $lexicalResults = $this->searchEngine->search($searchParams['lexical']);
            $semanticResults = $this->searchEngine->search($searchParams['semantic']);
            $mergedHits = $this->rankFusion->merge([
                $lexicalResults['hits']['hits'] ?? [],
                $semanticResults['hits']['hits'] ?? []
            ], [0.5, 0.5]);

            $resultsCount = count($mergedHits);
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // NEW: Log analytics asynchronously
            $this->analyticsCollector->logSearch(
                $request,
                $query,
                'hybrid_ai',
                $resultsCount,
                $duration
            );

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'hits' => $mergedHits,
                    'total' => $resultsCount,
                    'strategy' => 'hybrid_ai',
                    'duration_ms' => $duration,
                ],
            ]);
        }

        // Standard search
        $results = $this->searchEngine->search($searchParams);
        $resultsCount = $results['hits']['total']['value'] ?? 0;
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        // NEW: Log analytics asynchronously
        $this->analyticsCollector->logSearch(
            $request,
            $query,
            $strategy->value,
            $resultsCount,
            $duration
        );

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'hits' => $results['hits']['hits'] ?? [],
                'total' => $resultsCount,
                'strategy' => $strategy->value,
            ],
        ]);
    } catch (\Exception $e) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Search error occurred',
            'details' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
```

---

### **Phase 2: Analytics API (3-4 hours)**

#### Step 2.1: Create AnalyticsController
**File:** `src/Controller/AnalyticsController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SearchAnalyticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/analytics', name: 'api_analytics_')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly SearchAnalyticsRepository $analyticsRepository
    ) {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function overview(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $metrics = $this->analyticsRepository->getOverviewMetrics($startDate, $endDate);

        // Calculate derived metrics
        $totalSearches = (int) $metrics['totalSearches'];
        $clickedSearches = (int) $metrics['clickedSearches'];
        $zeroResultSearches = (int) $metrics['zeroResultSearches'];

        $clickThroughRate = $totalSearches > 0
            ? round(($clickedSearches / $totalSearches) * 100, 2)
            : 0;

        $successRate = $totalSearches > 0
            ? round((($totalSearches - $zeroResultSearches) / $totalSearches) * 100, 2)
            : 0;

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'total_searches' => $totalSearches,
                'unique_sessions' => (int) $metrics['uniqueSessions'],
                'avg_response_time_ms' => round((float) $metrics['avgResponseTime']),
                'zero_result_searches' => $zeroResultSearches,
                'click_through_rate' => $clickThroughRate,
                'success_rate' => $successRate,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $days,
                ],
            ],
        ]);
    }

    #[Route('/top-queries', name: 'top_queries', methods: ['GET'])]
    public function topQueries(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);

        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $queries = $this->analyticsRepository->getTopQueries($startDate, $endDate, $limit);

        // Format results
        $formatted = array_map(function ($item) {
            $searchCount = (int) $item['searchCount'];
            $clicks = (int) $item['clicks'];

            return [
                'query' => $item['query'],
                'search_count' => $searchCount,
                'avg_results' => round((float) $item['avgResults'], 1),
                'clicks' => $clicks,
                'click_rate' => $searchCount > 0
                    ? round(($clicks / $searchCount) * 100, 1)
                    : 0,
            ];
        }, $queries);

        return new JsonResponse([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    #[Route('/trends', name: 'trends', methods: ['GET'])]
    public function trends(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $trends = $this->analyticsRepository->getSearchTrends($startDate, $endDate);

        // Group by date and strategy
        $grouped = [];
        foreach ($trends as $item) {
            $date = $item['date']->format('Y-m-d');
            $strategy = $item['searchStrategy'];

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'total' => 0,
                    'by_strategy' => [],
                ];
            }

            $grouped[$date]['by_strategy'][$strategy] = (int) $item['searchCount'];
            $grouped[$date]['total'] += (int) $item['searchCount'];
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => array_values($grouped),
        ]);
    }

    #[Route('/click-positions', name: 'click_positions', methods: ['GET'])]
    public function clickPositions(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $positions = $this->analyticsRepository->getClickPositionDistribution($startDate, $endDate);

        $formatted = array_map(function ($item) {
            return [
                'position' => (int) $item['position'],
                'clicks' => (int) $item['clicks'],
            ];
        }, $positions);

        return new JsonResponse([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    #[Route('/zero-results', name: 'zero_results', methods: ['GET'])]
    public function zeroResults(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);

        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $queries = $this->analyticsRepository->getZeroResultQueries($startDate, $endDate, $limit);

        return new JsonResponse([
            'status' => 'success',
            'data' => $queries,
        ]);
    }
}
```

#### Step 2.2: Add Routes
**File:** `config/routes/analytics.yaml` (create new)

```yaml
# Analytics API Routes
analytics_dashboard:
    path: /analytics
    controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
    defaults:
        template: 'analytics/index.html.twig'
```

---

### **Phase 3: Frontend Dashboard (6-8 hours)**

#### Step 3.1: Install ApexCharts

```bash
npm install --save apexcharts vue3-apexcharts
```

#### Step 3.2: Register ApexCharts globally
**File:** `assets/app.js` (modify existing)

```javascript
import { createApp } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const app = createApp({});
app.use(VueApexCharts);

// Register global components
import Search from './components/search/Search.vue';
import Analytics from './pages/Analytics.vue';

app.component('Search', Search);
app.component('Analytics', Analytics);

app.mount('#app');
```

#### Step 3.3: Create KPI Card Component
**File:** `assets/components/analytics/KPICard.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ formattedValue }}</p>
        <p v-if="trend" class="text-sm mt-2" :class="trendClass">
          <span>{{ trend > 0 ? '↑' : '↓' }}</span>
          {{ Math.abs(trend) }}% vs previous period
        </p>
      </div>
      <div class="text-4xl" v-if="icon">{{ icon }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], required: true },
  trend: { type: Number, default: null },
  icon: { type: String, default: '' },
  suffix: { type: String, default: '' }
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString() + props.suffix;
  }
  return props.value;
});

const trendClass = computed(() => {
  if (!props.trend) return '';
  return props.trend > 0 ? 'text-green-600' : 'text-red-600';
});
</script>
```

#### Step 3.4: Create Trends Chart Component
**File:** `assets/components/analytics/TrendsChart.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Volume Trends</h3>
    <apexchart
      v-if="chartOptions"
      type="line"
      height="300"
      :options="chartOptions"
      :series="series"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      Loading chart...
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  data: { type: Array, default: () => [] }
});

const series = computed(() => {
  if (!props.data.length) return [];

  const strategies = ['hybrid_ai', 'exact', 'prefix'];
  return strategies.map(strategy => ({
    name: strategy.replace('_', ' ').toUpperCase(),
    data: props.data.map(item => item.by_strategy[strategy] || 0)
  }));
});

const chartOptions = computed(() => {
  if (!props.data.length) return null;

  return {
    chart: {
      type: 'line',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B'],
    stroke: { curve: 'smooth', width: 3 },
    xaxis: {
      categories: props.data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      })
    },
    yaxis: {
      title: { text: 'Searches' }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right'
    },
    tooltip: {
      shared: true,
      intersect: false
    }
  };
});
</script>
```

#### Step 3.5: Create Top Queries Chart
**File:** `assets/components/analytics/TopQueriesChart.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Search Queries</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Searches</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Results</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Click Rate</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, index) in data" :key="index" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.query }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ item.search_count }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ item.avg_results }}</td>
            <td class="px-4 py-3 text-sm text-right">
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getClickRateClass(item.click_rate)">
                {{ item.click_rate }}%
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  data: { type: Array, default: () => [] }
});

const getClickRateClass = (rate) => {
  if (rate >= 50) return 'bg-green-100 text-green-800';
  if (rate >= 25) return 'bg-yellow-100 text-yellow-800';
  return 'bg-red-100 text-red-800';
};
</script>
```

#### Step 3.6: Create Click Position Heatmap
**File:** `assets/components/analytics/ClickPositionHeatmap.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Click Position Distribution</h3>
    <apexchart
      v-if="chartOptions"
      type="bar"
      height="300"
      :options="chartOptions"
      :series="series"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      No click data available
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: { type: Array, default: () => [] }
});

const series = computed(() => {
  if (!props.data.length) return [];

  return [{
    name: 'Clicks',
    data: props.data.map(item => item.clicks)
  }];
});

const chartOptions = computed(() => {
  if (!props.data.length) return null;

  return {
    chart: {
      type: 'bar',
      toolbar: { show: false }
    },
    colors: ['#3B82F6'],
    plotOptions: {
      bar: {
        horizontal: true,
        distributed: true
      }
    },
    xaxis: {
      categories: props.data.map(item => `Position ${item.position}`),
      title: { text: 'Number of Clicks' }
    },
    yaxis: {
      title: { text: 'Result Position' }
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => val.toLocaleString()
    },
    legend: { show: false }
  };
});
</script>
```

#### Step 3.7: Create Strategy Distribution
**File:** `assets/components/analytics/StrategyDistribution.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Strategy Usage</h3>
    <apexchart
      v-if="chartOptions"
      type="donut"
      height="300"
      :options="chartOptions"
      :series="series"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      Loading chart...
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: { type: Object, default: () => ({}) }
});

const series = computed(() => {
  return [
    props.data.hybrid_ai || 0,
    props.data.exact || 0,
    props.data.prefix || 0
  ];
});

const chartOptions = computed(() => {
  return {
    chart: { type: 'donut' },
    labels: ['Hybrid AI', 'Exact', 'Prefix'],
    colors: ['#3B82F6', '#10B981', '#F59E0B'],
    legend: {
      position: 'bottom'
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => `${val.toFixed(1)}%`
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total Searches',
              formatter: (w) => {
                return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
              }
            }
          }
        }
      }
    }
  };
});
</script>
```

#### Step 3.8: Create Main Dashboard Component
**File:** `assets/pages/Analytics.vue`

```vue
<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="text-gray-600 mt-2">Search insights and performance metrics</p>
      </div>

      <!-- Date Range Selector -->
      <div class="mb-6 flex justify-end">
        <select
          v-model="selectedPeriod"
          @change="loadData"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="7">Last 7 days</option>
          <option value="14">Last 14 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div class="text-gray-500">Loading analytics...</div>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <KPICard
            title="Total Searches"
            :value="overview.total_searches"
            icon="🔍"
            :trend="12"
          />
          <KPICard
            title="Avg Response Time"
            :value="overview.avg_response_time_ms"
            suffix="ms"
            icon="⚡"
            :trend="-8"
          />
          <KPICard
            title="Success Rate"
            :value="overview.success_rate"
            suffix="%"
            icon="✅"
            :trend="3"
          />
          <KPICard
            title="Click Through Rate"
            :value="overview.click_through_rate"
            suffix="%"
            icon="🎯"
            :trend="5"
          />
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <TrendsChart :data="trends" />
          <StrategyDistribution :data="strategyDistribution" />
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <TopQueriesChart :data="topQueries" />
          <ClickPositionHeatmap :data="clickPositions" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import KPICard from '../components/analytics/KPICard.vue';
import TrendsChart from '../components/analytics/TrendsChart.vue';
import TopQueriesChart from '../components/analytics/TopQueriesChart.vue';
import ClickPositionHeatmap from '../components/analytics/ClickPositionHeatmap.vue';
import StrategyDistribution from '../components/analytics/StrategyDistribution.vue';

const isLoading = ref(false);
const selectedPeriod = ref('7');

const overview = ref({
  total_searches: 0,
  avg_response_time_ms: 0,
  success_rate: 0,
  click_through_rate: 0
});

const trends = ref([]);
const topQueries = ref([]);
const clickPositions = ref([]);

const strategyDistribution = computed(() => {
  // Calculate from trends data
  const totals = { hybrid_ai: 0, exact: 0, prefix: 0 };

  trends.value.forEach(item => {
    Object.keys(item.by_strategy).forEach(strategy => {
      totals[strategy] = (totals[strategy] || 0) + item.by_strategy[strategy];
    });
  });

  return totals;
});

const loadData = async () => {
  isLoading.value = true;

  try {
    const days = selectedPeriod.value;

    // Load all data in parallel
    const [overviewRes, trendsRes, queriesRes, positionsRes] = await Promise.all([
      fetch(`/api/analytics/overview?days=${days}`),
      fetch(`/api/analytics/trends?days=${days}`),
      fetch(`/api/analytics/top-queries?days=${days}`),
      fetch(`/api/analytics/click-positions?days=${days}`)
    ]);

    const [overviewData, trendsData, queriesData, positionsData] = await Promise.all([
      overviewRes.json(),
      trendsRes.json(),
      queriesRes.json(),
      positionsRes.json()
    ]);

    overview.value = overviewData.data;
    trends.value = trendsData.data;
    topQueries.value = queriesData.data;
    clickPositions.value = positionsData.data;
  } catch (error) {
    console.error('Failed to load analytics:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>
```

#### Step 3.9: Create Template Entry Point
**File:** `templates/analytics/index.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Analytics Dashboard{% endblock %}

{% block body %}
    <div id="app">
        <Analytics />
    </div>
{% endblock %}
```

---

### **Phase 4: Testing & Documentation (2-3 hours)**

#### Step 4.1: Run Migration

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

#### Step 4.2: Generate Some Test Data

```bash
# Perform some test searches to generate data
curl "http://localhost/api/search?q=machine+learning"
curl "http://localhost/api/search?q=neural+networks"
curl "http://localhost/api/search?q=deep+learning"
```

#### Step 4.3: Process Messenger Queue

```bash
docker compose exec php php bin/console messenger:consume async -vv
```

#### Step 4.4: Test API Endpoints

```bash
curl http://localhost/api/analytics/overview?days=7
curl http://localhost/api/analytics/top-queries?days=7
curl http://localhost/api/analytics/trends?days=7
```

#### Step 4.5: Access Dashboard

```
http://localhost/analytics
```

#### Step 4.6: Update README
Add section to [README.md](../README.md):

```markdown
## Analytics Dashboard

Track search behavior and performance metrics:

```bash
# Access dashboard
http://localhost/analytics

# View API endpoints
GET /api/analytics/overview?days=7
GET /api/analytics/top-queries?days=7&limit=20
GET /api/analytics/trends?days=7
GET /api/analytics/click-positions?days=7
GET /api/analytics/zero-results?days=7&limit=20
```

Metrics tracked:
- Search volume and trends
- Response time performance
- Click-through rates
- Top queries
- Zero-result queries (content gaps)
- Search strategy distribution
```

---

## 🔒 GDPR Compliance

1. **IP Anonymization:** Last octet removed (implemented in `LogSearchAnalyticsHandler`)
2. **Data Retention:** 90 days (configure cron job to delete old records)
3. **No PII Storage:** No names, emails, or identifying information
4. **Session IDs:** Rotated regularly by Symfony

### Cleanup Command (Optional)
**File:** `src/Command/CleanupAnalyticsCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cleanup-analytics',
    description: 'Delete analytics data older than 90 days (GDPR compliance)'
)]
class CleanupAnalyticsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cutoffDate = new \DateTime('-90 days');

        $deleted = $this->entityManager->createQuery(
            'DELETE FROM App\Entity\SearchAnalytics sa WHERE sa.createdAt < :cutoff'
        )
        ->setParameter('cutoff', $cutoffDate)
        ->execute();

        $output->writeln("Deleted {$deleted} analytics records older than 90 days.");

        return Command::SUCCESS;
    }
}
```

**Add to crontab:**
```bash
0 2 * * * cd /var/www/html && php bin/console app:cleanup-analytics
```

---

## 🚀 Deployment Checklist

- [ ] Run migration: `php bin/console doctrine:migrations:migrate`
- [ ] Install npm packages: `npm install`
- [ ] Build frontend: `npm run build`
- [ ] Restart workers: `docker compose restart messenger-worker`
- [ ] Test analytics API endpoints
- [ ] Verify dashboard loads correctly
- [ ] Configure cleanup cron job (GDPR)
- [ ] Update documentation

---

## 📈 Future Enhancements

### Phase 5 (Optional):
1. **Frontend Click Tracking**
   - Capture when users click on search results
   - Track time-to-click metrics
   - Update `clicked` fields in database

2. **Export Functionality**
   - Export dashboard data to CSV/PDF
   - Scheduled email reports

3. **Real-time Dashboard**
   - WebSocket updates
   - Live search counter

4. **Advanced Analytics**
   - User journey mapping
   - A/B testing framework
   - ML-powered query suggestions

5. **Alerts**
   - Email notifications for anomalies
   - Slack integration for zero-result spikes

---

## 🎯 Success Metrics

After implementation, you should be able to answer:

✅ What are users searching for?
✅ Which searches fail (zero results)?
✅ What's the average search performance?
✅ Which PDFs are most popular?
✅ What's the click-through rate?
✅ Which search strategy performs best?

---

## 📚 References

- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Doctrine Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/query-builder.html)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [ApexCharts Documentation](https://apexcharts.com/docs/vue-charts/)
- [Tailwind CSS](https://tailwindcss.com/docs)

---

**Document Version:** 1.0
**Last Updated:** 2025-12-10
**Author:** Analytics Dashboard Implementation Team
