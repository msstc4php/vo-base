<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Exception;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\Eui64;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AmbiguousValueException::class)]
#[UsesClass(InvalidValueObjectException::class)]
final class AmbiguousValueExceptionTest extends TestCase
{
    public function testKeepsCandidatesInOrder(): void
    {
        $exception = new AmbiguousValueException('identifier', [Eui64::class, MacAddress::class]);

        self::assertSame([Eui64::class, MacAddress::class], $exception->candidates());
    }

    public function testMessageNamesTypeAndCandidatesOnly(): void
    {
        $exception = new AmbiguousValueException('identifier', [Eui64::class, MacAddress::class]);

        self::assertSame('Ambiguous identifier: the value fits Eui64, MacAddress.', $exception->getMessage());
    }

    public function testIsCaughtAsAnInvalidValueObjectException(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        throw new AmbiguousValueException('identifier', [Eui64::class]);
    }
}
