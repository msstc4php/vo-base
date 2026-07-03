<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidBase64StringException;
use MSSTC4PHP\ValueObject\Format\Base64String;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Base64String::class)]
final class Base64StringTest extends TestCase
{
    public function testConstructsAndDecodesRoundTrip(): void
    {
        $encoded = \base64_encode('hello world');

        $value = Base64String::from($encoded);

        self::assertSame($encoded, $value->value);
        self::assertSame('hello world', $value->decoded());
    }

    public function testRejectsNonBase64(): void
    {
        $this->expectException(InvalidBase64StringException::class);

        Base64String::from('not@base64!');
    }
}
