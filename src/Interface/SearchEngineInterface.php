<?php

namespace App\Interface;

interface SearchEngineInterface
{
    public function index(string $index, string $id, array $data): void;
    public function search(string $index, string $query): array;
}