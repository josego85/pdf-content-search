<?php

declare(strict_types=1);

namespace App\Contract;

interface SearchEngineInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function indexDocument(string $index, string $id, array $data): void;

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function search(array $query): array;
}
