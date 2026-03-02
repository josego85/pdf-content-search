<?php

declare(strict_types=1);

namespace App\Message;

final readonly class LogSearchAnalyticsMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
