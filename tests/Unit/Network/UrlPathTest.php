<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlPathException;
use MSSTC4PHP\ValueObject\Network\UrlPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlPath::class)]
final class UrlPathTest extends TestCase
{
    public function testConstructsFromValidPath(): void
    {
        self::assertSame('/a/b', UrlPath::from('/a/b')->value);
    }

    public function testEmptyPathIsAllowed(): void
    {
        self::assertSame('', UrlPath::from('')->value);
    }

    public function testRawSpaceThrows(): void
    {
        $this->expectException(InvalidUrlPathException::class);

        UrlPath::from('/a b');
    }
}
