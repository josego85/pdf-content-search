<?php

declare(strict_types=1);

namespace App\Contract;

interface LanguageDetectorInterface
{
    /**
     * @return array{language: string, confidence: float, all: array<string, float>}
     */
    public function detect(string $text): array;
}
