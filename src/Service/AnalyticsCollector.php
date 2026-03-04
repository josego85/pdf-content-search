<?php

declare(strict_types=1);

namespace App\Service;

use App\Message\LogSearchAnalyticsMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AnalyticsCollector
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $searchPageSize,
    ) {
    }

    /**
     * Log search analytics asynchronously.
     * Frontend controls what gets logged via log=1/0 parameter.
     */
    public function logSearch(
        Request $request,
        string $query,
        string $searchStrategy,
        int $resultsCount,
        int $responseTimeMs
    ): void {
        // Only validate that query is not empty
        $cleanQuery = trim($query, " \t\n\r\0\x0B\"'");

        // Don't log empty queries or only quotes
        if ($cleanQuery === '' || $cleanQuery === '0' || $query === '""' || $query === "''") {
            return;
        }

        // Log everything else - frontend decides what's worth logging
        $data = [
            'session_id' => $request->getSession()->getId(),
            'query' => $query,
            'search_strategy' => $searchStrategy,
            'results_count' => $resultsCount,
            'displayed_results_count' => min($resultsCount, $this->searchPageSize),
            'response_time_ms' => $responseTimeMs,
            'user_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('Referer'),
        ];

        $this->messageBus->dispatch(new LogSearchAnalyticsMessage($data));
    }

    /**
     * Log click event (to be implemented in future phase).
     */
    public function logClick(): void
    {
        // TODO: Implement click tracking in Phase 5
        // This will update existing SearchAnalytics records with click data
    }
}
