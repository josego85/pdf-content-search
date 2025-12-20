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
