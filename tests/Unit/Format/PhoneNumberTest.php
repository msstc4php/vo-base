<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidPhoneNumberException;
use MSSTC4PHP\ValueObject\Format\PhoneNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhoneNumber::class)]
final class PhoneNumberTest extends TestCase
{
    public function testAcceptsE164Number(): void
    {
        self::assertSame('+14155552671', PhoneNumber::from('+14155552671')->value);
    }

    public function testRejectsMissingPlus(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneNumber::from('14155552671');
    }

    public function testRejectsTooManyDigits(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneNumber::from('+1234567890123456');
    }
}
