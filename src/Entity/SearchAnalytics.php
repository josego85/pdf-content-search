<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SearchAnalyticsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchAnalyticsRepository::class)]
#[ORM\Table(name: 'search_analytics')]
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
