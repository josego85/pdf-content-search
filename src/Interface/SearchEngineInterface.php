<?php

namespace App\Interface;

interface SearchEngineInterface
{
    public function index(string $index, string $id, array $data): void;

    public function search(array $query): array;
}
