<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use Exception;
use JsonSerializable;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidClassStringException;
use MSSTC4PHP\ValueObject\Format\ClassString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(ClassString::class)]
final class ClassStringTest extends TestCase
{
    public function testConstructsFromExistingClass(): void
    {
        self::assertSame(RuntimeException::class, ClassString::from(RuntimeException::class)->value);
    }

    public function testConstructsFromExistingInterface(): void
    {
        self::assertSame(ValueObject::class, ClassString::from(ValueObject::class)->value);
    }

    public function testRejectsUnknownName(): void
    {
        $this->expectException(InvalidClassStringException::class);

        ClassString::from('Totally\\Missing\\Type');
    }

    public function testIsInstanceOf(): void
    {
        $type = ClassString::from(Exception::class);

        self::assertTrue($type->isInstanceOf(new RuntimeException()));
        self::assertFalse($type->isInstanceOf(new stdClass()));
    }

    public function testIsSubclassOf(): void
    {
        $type = ClassString::from(RuntimeException::class);

        self::assertTrue($type->isSubclassOf(Exception::class));
        self::assertFalse($type->isSubclassOf(JsonSerializable::class));
    }

    public function testTryFromReturnsNullForUnknownName(): void
    {
        self::assertNull(ClassString::tryFrom('Totally\\Missing\\Type'));
        self::assertFalse(ClassString::isValid('Totally\\Missing\\Type'));
    }

    public function testTryFromReturnsInstanceForKnownName(): void
    {
        self::assertInstanceOf(ClassString::class, ClassString::tryFrom(RuntimeException::class));
        self::assertTrue(ClassString::isValid(RuntimeException::class));
    }

    public function testStringConversionAndJsonSerialization(): void
    {
        $type = ClassString::from(RuntimeException::class);

        self::assertSame(RuntimeException::class, (string) $type);
        self::assertSame(RuntimeException::class, $type->jsonSerialize());
    }

    public function testEquals(): void
    {
        $type = ClassString::from(RuntimeException::class);

        self::assertTrue($type->equals(ClassString::from(RuntimeException::class)));
        self::assertFalse($type->equals(ClassString::from(Exception::class)));
    }
}
