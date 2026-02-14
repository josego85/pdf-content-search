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
     * Anonymize IP for GDPR compliance.
     */
    private function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Keep first 3 octets, zero last octet
            $lastDot = strrpos($ip, '.');

            return $lastDot !== false ? substr($ip, 0, $lastDot) . '.0' : '0.0.0.0';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Keep first 4 groups, zero the rest
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 4)) . '::';
        }

        return '0.0.0.0';
    }
}
