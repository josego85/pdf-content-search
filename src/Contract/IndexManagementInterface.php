<?php

declare(strict_types=1);

namespace App\Contract;

interface IndexManagementInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function createIndex(array $settings = []): void;

    public function deleteIndex(): void;
}
