<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\TranslatePageMessage;
use PHPUnit\Framework\TestCase;

final class TranslatePageMessageTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $message = new TranslatePageMessage(
            'document.pdf',
            42,
            'es',
            'This is the original text to translate.'
        );

        self::assertSame('document.pdf', $message->getPdfFilename());
        self::assertSame(42, $message->getPageNumber());
        self::assertSame('es', $message->getTargetLanguage());
        self::assertSame('This is the original text to translate.', $message->getOriginalText());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $pdfFilename = 'test-file.pdf';
        $pageNumber = 123;
        $targetLanguage = 'fr';
        $originalText = 'Sample text';

        $message = new TranslatePageMessage($pdfFilename, $pageNumber, $targetLanguage, $originalText);

        self::assertSame($pdfFilename, $message->getPdfFilename());
        self::assertSame($pageNumber, $message->getPageNumber());
        self::assertSame($targetLanguage, $message->getTargetLanguage());
        self::assertSame($originalText, $message->getOriginalText());
    }

    public function testMessageWithEmptyText(): void
    {
        $message = new TranslatePageMessage('file.pdf', 1, 'en', '');

        self::assertSame('', $message->getOriginalText());
    }

    public function testMessageWithLongText(): void
    {
        $longText = str_repeat('A very long text to translate. ', 1000);
        $message = new TranslatePageMessage('file.pdf', 1, 'en', $longText);

        self::assertSame($longText, $message->getOriginalText());
    }

    public function testMessageWithSpecialCharacters(): void
    {
        $text = 'Spécial çhãràctërs: ñ, é, ü, ñ, 中文, 日本語, العربية';
        $message = new TranslatePageMessage('file.pdf', 1, 'es', $text);

        self::assertSame($text, $message->getOriginalText());
    }

    public function testMessageWithPageNumber1(): void
    {
        $message = new TranslatePageMessage('file.pdf', 1, 'es', 'text');

        self::assertSame(1, $message->getPageNumber());
    }

    public function testMessageWithLargePageNumber(): void
    {
        $message = new TranslatePageMessage('file.pdf', 99999, 'es', 'text');

        self::assertSame(99999, $message->getPageNumber());
    }
}
