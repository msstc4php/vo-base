<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Tests\Fixtures\UnitFraction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[CoversClass(AbstractFloatValueObject::class)]
#[UsesClass(Percentage::class)]
final class AbstractFloatValueObjectTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(0.0, UnitFraction::from(0.0)->value);
        self::assertSame(1.0, UnitFraction::from(1.0)->value);
    }

    public function testRejectsOutOfBounds(): void
    {
        self::assertNull(UnitFraction::tryFrom(-0.1));
        self::assertNull(UnitFraction::tryFrom(1.1));
    }

    public function testCompareToOrdersByValue(): void
    {
        self::assertSame(-1, UnitFraction::from(0.25)->compareTo(UnitFraction::from(0.75)));
        self::assertSame(0, UnitFraction::from(0.5)->compareTo(UnitFraction::from(0.5)));
    }

    public function testCompareToRejectsDifferentType(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        UnitFraction::from(0.5)->compareTo(Percentage::from(50.0));
    }

    public function testEqualsIsFalseForDifferentType(): void
    {
        self::assertFalse(UnitFraction::from(0.5)->equals(Percentage::from(50.0)));
    }

    public function testIsValidAndConversions(): void
    {
        self::assertTrue(UnitFraction::isValid(0.5));
        self::assertFalse(UnitFraction::isValid(2.0));
        self::assertSame('0.5', (string) UnitFraction::from(0.5));
        self::assertSame(0.5, UnitFraction::from(0.5)->jsonSerialize());
    }

    public function testSerializationRoundTripsTheValueAlone(): void
    {
        $original = UnitFraction::from(0.25);
        $payload = \serialize($original);

        self::assertSame($this->payload('s:5:"value";' . \serialize(0.25)), $payload);
        self::assertTrue($original->equals($this->restored($payload)));
    }

    public function testAForgedInvalidPayloadIsRefused(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        $this->forged(1.5);
    }

    public function testAnIntegerPayloadIsTakenAsAFloat(): void
    {
        $restored = $this->restored($this->payload('s:5:"value";i:1;'));

        self::assertSame(1.0, $restored->value);
    }

    public function testAPayloadWithoutAFloatValueIsRefusedWithoutEchoingIt(): void
    {
        foreach ([\serialize('0.25'), 'N;'] as $raw) {
            $this->assertRefusedWithoutEcho('s:5:"value";' . $raw);
        }
    }

    public function testAPayloadWithoutAValueKeyIsRefused(): void
    {
        $this->assertRefusedWithoutEcho('s:5:"other";' . \serialize(0.25));
    }

    public function testExtraPayloadKeysAreIgnored(): void
    {
        $payload = $this->payload('s:5:"value";' . \serialize(0.25) . 's:5:"extra";i:1;', 2);

        self::assertTrue(UnitFraction::from(0.25)->equals($this->restored($payload)));
    }

    public function testAPayloadWithAPrivatePropertyOfASubclassStillUnserializes(): void
    {
        $key = "\0" . UnitFraction::class . "\0cache";
        $payload = $this->payload('s:5:"value";' . \serialize(0.25) . \serialize($key) . 'i:1;', 2);

        self::assertTrue(UnitFraction::from(0.25)->equals($this->restored($payload)));
    }

    public function testUnserializeRefusesAnInstanceThatHoldsAValue(): void
    {
        $this->expectException(LogicException::class);

        UnitFraction::from(0.25)->__unserialize(['value' => 0.25]);
    }

    private function assertRefusedWithoutEcho(string $body): void
    {
        try {
            \unserialize($this->payload($body));
            self::fail('Expected an exception.');
        } catch (InvalidValueObjectException $e) {
            self::assertSame('A serialized ' . UnitFraction::class . ' must hold its value as a float.', $e->getMessage());
        }
    }

    private function forged(float $value): UnitFraction
    {
        return $this->restored($this->payload('s:5:"value";' . \serialize($value)));
    }

    private function payload(string $body, int $count = 1): string
    {
        return \sprintf('O:%d:"%s":%d:{%s}', \strlen(UnitFraction::class), UnitFraction::class, $count, $body);
    }

    private function restored(string $payload): UnitFraction
    {
        $object = \unserialize($payload);
        self::assertInstanceOf(UnitFraction::class, $object);

        return $object;
    }
}
