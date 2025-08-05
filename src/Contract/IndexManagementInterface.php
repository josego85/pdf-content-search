<?php

declare(strict_types=1);

namespace App\Contract;

interface IndexManagementInterface
{
    public function createIndex(array $settings = []): void;

    public function deleteIndex(): void;
}
