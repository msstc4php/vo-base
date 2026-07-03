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
}
