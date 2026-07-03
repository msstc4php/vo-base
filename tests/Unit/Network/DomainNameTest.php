<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidDomainNameException;
use MSSTC4PHP\ValueObject\Network\DomainName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DomainName::class)]
final class DomainNameTest extends TestCase
{
    public function testConstructsFromValidDomain(): void
    {
        self::assertSame('example.com', DomainName::from('example.com')->value);
    }

    public function testSingleLabelThrows(): void
    {
        $this->expectException(InvalidDomainNameException::class);

        DomainName::from('localhost');
    }

    public function testNumericTldThrows(): void
    {
        $this->expectException(InvalidDomainNameException::class);

        DomainName::from('a.1');
    }

    public function testEmptyValueThrows(): void
    {
        $this->expectException(InvalidDomainNameException::class);

        DomainName::from('');
    }

    public function testInvalidLabelCharactersThrow(): void
    {
        $this->expectException(InvalidDomainNameException::class);

        DomainName::from('bad_label.com');
    }

    public function testLowercasesAndExposesValue(): void
    {
        self::assertSame('sub.example.com', DomainName::from('SUB.Example.COM')->value);
    }

    public function testTryFromIsValidAndSerialization(): void
    {
        self::assertNull(DomainName::tryFrom('localhost'));
        self::assertTrue(DomainName::isValid('example.com'));
        self::assertSame('example.com', DomainName::from('example.com')->jsonSerialize());
        self::assertSame('example.com', (string) DomainName::from('example.com'));
    }
}
