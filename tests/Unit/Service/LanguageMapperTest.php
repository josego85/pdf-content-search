<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LanguageMapper;
use PHPUnit\Framework\TestCase;

final class LanguageMapperTest extends TestCase
{
    private LanguageMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LanguageMapper();
    }

    public function testGetFullNameWithValidCode(): void
    {
        self::assertSame('Spanish', $this->mapper->getFullName('es'));
        self::assertSame('English', $this->mapper->getFullName('en'));
        self::assertSame('French', $this->mapper->getFullName('fr'));
        self::assertSame('German', $this->mapper->getFullName('de'));
        self::assertSame('Portuguese', $this->mapper->getFullName('pt'));
        self::assertSame('Italian', $this->mapper->getFullName('it'));
        self::assertSame('Dutch', $this->mapper->getFullName('nl'));
        self::assertSame('Polish', $this->mapper->getFullName('pl'));
        self::assertSame('Russian', $this->mapper->getFullName('ru'));
    }

    public function testGetFullNameWithUnknownCode(): void
    {
        self::assertSame('unknown', $this->mapper->getFullName('unknown'));
        self::assertSame('xyz', $this->mapper->getFullName('xyz'));
    }

    public function testGetLabelWithValidCode(): void
    {
        self::assertSame('ES', $this->mapper->getLabel('es'));
        self::assertSame('EN', $this->mapper->getLabel('en'));
        self::assertSame('FR', $this->mapper->getLabel('fr'));
    }

    public function testGetLabelWithUnknownCode(): void
    {
        self::assertSame('UNKNOWN', $this->mapper->getLabel('unknown'));
        self::assertSame('XYZ', $this->mapper->getLabel('xyz'));
    }

    public function testIsSupportedWithValidCodes(): void
    {
        self::assertTrue($this->mapper->isSupported('es'));
        self::assertTrue($this->mapper->isSupported('en'));
        self::assertTrue($this->mapper->isSupported('fr'));
        self::assertTrue($this->mapper->isSupported('de'));
    }

    public function testIsSupportedWithInvalidCodes(): void
    {
        self::assertFalse($this->mapper->isSupported('unknown'));
        self::assertFalse($this->mapper->isSupported(''));
        self::assertFalse($this->mapper->isSupported('xyz'));
    }

    public function testGetSupportedCodes(): void
    {
        $codes = $this->mapper->getSupportedCodes();

        self::assertIsArray($codes);
        self::assertNotEmpty($codes);
        self::assertContains('es', $codes);
        self::assertContains('en', $codes);
        self::assertContains('fr', $codes);
    }

    public function testGetAllLanguages(): void
    {
        $languages = $this->mapper->getAllLanguages();

        self::assertIsArray($languages);
        self::assertNotEmpty($languages);
        self::assertArrayHasKey('es', $languages);
        self::assertArrayHasKey('en', $languages);

        // Check structure
        self::assertArrayHasKey('label', $languages['es']);
        self::assertArrayHasKey('fullName', $languages['es']);
        self::assertSame('ES', $languages['es']['label']);
        self::assertSame('Spanish', $languages['es']['fullName']);
    }
}
