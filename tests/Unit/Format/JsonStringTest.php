<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidJsonStringException;
use MSSTC4PHP\ValueObject\Format\JsonString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonString::class)]
final class JsonStringTest extends TestCase
{
    public function testAcceptsValidJson(): void
    {
        self::assertSame('{"a":1}', JsonString::from('{"a":1}')->value);
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(InvalidJsonStringException::class);

        JsonString::from('{a:1}');
    }
}
