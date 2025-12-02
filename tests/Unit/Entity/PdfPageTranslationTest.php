<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\PdfPageTranslation;
use PHPUnit\Framework\TestCase;

final class PdfPageTranslationTest extends TestCase
{
    public function testConstructorSetsTimestamps(): void
    {
        $translation = new PdfPageTranslation();

        self::assertInstanceOf(\DateTimeImmutable::class, $translation->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $translation->getUpdatedAt());
    }

    public function testSetAndGetPdfFilename(): void
    {
        $translation = new PdfPageTranslation();
        $translation->setPdfFilename('document.pdf');

        self::assertSame('document.pdf', $translation->getPdfFilename());
    }

    public function testSetAndGetPageNumber(): void
    {
        $translation = new PdfPageTranslation();
        $translation->setPageNumber(42);

        self::assertSame(42, $translation->getPageNumber());
    }

    public function testSetAndGetSourceLanguage(): void
    {
        $translation = new PdfPageTranslation();
        $translation->setSourceLanguage('en');

        self::assertSame('en', $translation->getSourceLanguage());
    }

    public function testSetAndGetTargetLanguage(): void
    {
        $translation = new PdfPageTranslation();
        $translation->setTargetLanguage('es');

        self::assertSame('es', $translation->getTargetLanguage());
    }

    public function testSetAndGetOriginalText(): void
    {
        $translation = new PdfPageTranslation();
        $text = 'This is the original text';
        $translation->setOriginalText($text);

        self::assertSame($text, $translation->getOriginalText());
    }

    public function testSetAndGetTranslatedText(): void
    {
        $translation = new PdfPageTranslation();
        $text = 'Este es el texto traducido';
        $translation->setTranslatedText($text);

        self::assertSame($text, $translation->getTranslatedText());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $translation = new PdfPageTranslation();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $translation->setUpdatedAt($date);

        self::assertSame($date, $translation->getUpdatedAt());
    }

    public function testGetIdReturnsNullForNewEntity(): void
    {
        $translation = new PdfPageTranslation();

        self::assertNull($translation->getId());
    }

    public function testFluentInterface(): void
    {
        $translation = new PdfPageTranslation();

        $result = $translation
            ->setPdfFilename('test.pdf')
            ->setPageNumber(1)
            ->setSourceLanguage('en')
            ->setTargetLanguage('es')
            ->setOriginalText('Original')
            ->setTranslatedText('Traducido');

        self::assertInstanceOf(PdfPageTranslation::class, $result);
        self::assertSame($translation, $result);
    }

    public function testCompleteTranslationWorkflow(): void
    {
        $translation = new PdfPageTranslation();
        $translation->setPdfFilename('document.pdf');
        $translation->setPageNumber(5);
        $translation->setSourceLanguage('en');
        $translation->setTargetLanguage('fr');
        $translation->setOriginalText('Hello world');
        $translation->setTranslatedText('Bonjour le monde');

        self::assertSame('document.pdf', $translation->getPdfFilename());
        self::assertSame(5, $translation->getPageNumber());
        self::assertSame('en', $translation->getSourceLanguage());
        self::assertSame('fr', $translation->getTargetLanguage());
        self::assertSame('Hello world', $translation->getOriginalText());
        self::assertSame('Bonjour le monde', $translation->getTranslatedText());
    }
}
