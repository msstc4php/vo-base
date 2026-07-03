<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidPortException;
use MSSTC4PHP\ValueObject\Network\Port;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Port::class)]
final class PortTest extends TestCase
{
    public function testConstructsFromValidPorts(): void
    {
        self::assertSame(0, Port::from(0)->value);
        self::assertSame(443, Port::from(443)->value);
        self::assertSame(65535, Port::from(65535)->value);
    }

    public function testNegativePortThrows(): void
    {
        $this->expectException(InvalidPortException::class);

        Port::from(-1);
    }

    public function testPortAboveMaximumThrows(): void
    {
        $this->expectException(InvalidPortException::class);

        Port::from(65536);
    }
}
