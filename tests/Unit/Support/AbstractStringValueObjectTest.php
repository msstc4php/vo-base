<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Tests\Fixtures\LowercaseWord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractStringValueObject::class)]
final class AbstractStringValueObjectTest extends TestCase
{
    public function testFromConstructsAndNormalizes(): void
    {
        $word = LowercaseWord::from('  Hello  ');

        self::assertSame('hello', $word->value);
        self::assertSame('hello', (string) $word);
        self::assertSame('hello', $word->jsonSerialize());
    }

    public function testFromThrowsTypedExceptionOnInvalidInput(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        LowercaseWord::from('   ');
    }

    public function testTryFromReturnsNullOnInvalidInput(): void
    {
        self::assertNull(LowercaseWord::tryFrom(''));
        self::assertInstanceOf(LowercaseWord::class, LowercaseWord::tryFrom('ok'));
    }

    public function testIsValid(): void
    {
        self::assertTrue(LowercaseWord::isValid('word'));
        self::assertFalse(LowercaseWord::isValid(''));
    }

    public function testEqualsComparesNormalizedValueAndType(): void
    {
        self::assertTrue(LowercaseWord::from('ABC')->equals(LowercaseWord::from('abc')));
        self::assertFalse(LowercaseWord::from('abc')->equals(LowercaseWord::from('abd')));
    }

    public function testJsonEncodesToPlainString(): void
    {
        self::assertSame('"hello"', \json_encode(LowercaseWord::from('hello')));
    }
}
