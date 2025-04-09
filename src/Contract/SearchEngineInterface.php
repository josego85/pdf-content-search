<?php

declare(strict_types=1);

namespace App\Contract;

interface SearchEngineInterface
{
    public function indexDocument(string $index, string $id, array $data): void;

    public function search(array $query): array;
}
