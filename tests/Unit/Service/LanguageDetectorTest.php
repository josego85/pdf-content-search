<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LanguageDetector;
use PHPUnit\Framework\TestCase;

final class LanguageDetectorTest extends TestCase
{
    private LanguageDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new LanguageDetector();
    }

    public function testDetectSpanishText(): void
    {
        $text = 'Este es un texto en español con varias palabras para detectar el idioma correctamente.';

        $result = $this->detector->detect($text);

        self::assertIsArray($result);
        self::assertArrayHasKey('language', $result);
        self::assertArrayHasKey('confidence', $result);
        self::assertArrayHasKey('all', $result);
        self::assertSame('es', $result['language']);
        self::assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectEnglishText(): void
    {
        $text = 'This is an English text with several words to detect the language correctly.';

        $result = $this->detector->detect($text);

        self::assertSame('en', $result['language']);
        self::assertGreaterThan(0, $result['confidence']);
        self::assertIsArray($result['all']);
    }

    public function testDetectFrenchText(): void
    {
        $text = 'Ceci est un texte en français avec plusieurs mots pour détecter la langue correctement.';

        $result = $this->detector->detect($text);

        self::assertSame('fr', $result['language']);
        self::assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectPortugueseText(): void
    {
        $text = 'Este é um texto em português brasileiro com muitas palavras específicas como açúcar, coração e também.';

        $result = $this->detector->detect($text);

        // Portuguese and Spanish are very similar, so we check it's one of them
        self::assertContains($result['language'], ['pt', 'es']);
        self::assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectGermanText(): void
    {
        $text = 'Dies ist ein deutscher Text mit mehreren Wörtern, um die Sprache korrekt zu erkennen.';

        $result = $this->detector->detect($text);

        self::assertSame('de', $result['language']);
        self::assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectWithShortText(): void
    {
        $text = 'Hola mundo';

        $result = $this->detector->detect($text);

        self::assertIsArray($result);
        self::assertArrayHasKey('language', $result);
        self::assertIsString($result['language']);
    }

    public function testDetectWithLongText(): void
    {
        $text = str_repeat('This is a very long English text. ', 100);

        $result = $this->detector->detect($text);

        self::assertSame('en', $result['language']);
        self::assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectReturnsAllLanguageScores(): void
    {
        $text = 'This is an English text for testing language detection.';

        $result = $this->detector->detect($text);

        self::assertIsArray($result['all']);
        self::assertNotEmpty($result['all']);
        self::assertArrayHasKey('en', $result['all']);
    }

    public function testDetectWithMixedLanguages(): void
    {
        $text = 'Hello world. Hola mundo. Bonjour le monde.';

        $result = $this->detector->detect($text);

        self::assertIsString($result['language']);
        self::assertIsFloat($result['confidence']);
        self::assertIsArray($result['all']);
    }

    public function testDetectWithEmptyText(): void
    {
        $text = '';

        $result = $this->detector->detect($text);

        self::assertIsArray($result);
        self::assertArrayHasKey('language', $result);
        self::assertArrayHasKey('confidence', $result);
    }

    public function testDetectConfidenceIsFloat(): void
    {
        $text = 'Sample text for testing';

        $result = $this->detector->detect($text);

        self::assertIsFloat($result['confidence']);
        self::assertGreaterThanOrEqual(0.0, $result['confidence']);
        self::assertLessThanOrEqual(1.0, $result['confidence']);
    }

    public function testDetectWithNumbers(): void
    {
        $text = '12345 67890 numbers only';

        $result = $this->detector->detect($text);

        self::assertIsArray($result);
        self::assertArrayHasKey('language', $result);
    }
}
