<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use MSSTC4PHP\ValueObject\Tests\Fixtures\BoundedNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(FreshInstance::class)]
#[CoversClass(AbstractIntValueObject::class)]
final class AbstractIntValueObjectTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(0, BoundedNumber::from(0)->value);
        self::assertSame(10, BoundedNumber::from(10)->value);
        self::assertSame(5, BoundedNumber::from(5)->jsonSerialize());
    }

    public function testRejectsOutOfBounds(): void
    {
        self::assertNull(BoundedNumber::tryFrom(-1));
        self::assertNull(BoundedNumber::tryFrom(11));
        self::assertSame(5, BoundedNumber::tryFrom(5)?->value);
        self::assertFalse(BoundedNumber::isValid(11));
    }

    public function testThrowsTypedExceptionOnInvalidInput(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        BoundedNumber::from(11);
    }

    public function testEquals(): void
    {
        self::assertTrue(BoundedNumber::from(3)->equals(BoundedNumber::from(3)));
        self::assertFalse(BoundedNumber::from(3)->equals(BoundedNumber::from(4)));
    }

    public function testCompareToOrdersByValue(): void
    {
        self::assertSame(-1, BoundedNumber::from(1)->compareTo(BoundedNumber::from(2)));
        self::assertSame(0, BoundedNumber::from(2)->compareTo(BoundedNumber::from(2)));
        self::assertSame(1, BoundedNumber::from(3)->compareTo(BoundedNumber::from(2)));
    }

    public function testStringForm(): void
    {
        self::assertSame('7', (string) BoundedNumber::from(7));
    }

    public function testSerializationRoundTripsTheValueAlone(): void
    {
        $original = BoundedNumber::from(7);
        $payload = \serialize($original);

        self::assertSame($this->payload('s:5:"value";' . \serialize(7)), $payload);
        self::assertTrue($original->equals($this->restored($payload)));
    }

    public function testAForgedInvalidPayloadIsRefused(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        $this->forged(11);
    }

    public function testAPayloadWithoutAnIntValueIsRefusedWithoutEchoingIt(): void
    {
        foreach ([\serialize('7'), \serialize(7.0), 'N;'] as $raw) {
            $this->assertRefusedWithoutEcho('s:5:"value";' . $raw);
        }
    }

    public function testAPayloadWithoutAValueKeyIsRefused(): void
    {
        $this->assertRefusedWithoutEcho('s:5:"other";' . \serialize(7));
    }

    public function testExtraPayloadKeysAreIgnored(): void
    {
        $payload = $this->payload('s:5:"value";' . \serialize(7) . 's:5:"extra";i:1;', 2);

        self::assertTrue(BoundedNumber::from(7)->equals($this->restored($payload)));
    }

    public function testAPayloadWithAPrivatePropertyOfASubclassStillUnserializes(): void
    {
        $key = "\0" . BoundedNumber::class . "\0cache";
        $payload = $this->payload('s:5:"value";' . \serialize(7) . \serialize($key) . 'i:1;', 2);

        self::assertTrue(BoundedNumber::from(7)->equals($this->restored($payload)));
    }

    public function testUnserializeRefusesAnInstanceThatHoldsAValue(): void
    {
        $this->expectException(LogicException::class);

        BoundedNumber::from(7)->__unserialize(['value' => 7]);
    }

    private function assertRefusedWithoutEcho(string $body): void
    {
        try {
            \unserialize($this->payload($body));
            self::fail('Expected an exception.');
        } catch (InvalidValueObjectException $e) {
            self::assertSame('A serialized ' . BoundedNumber::class . ' must hold its value as an int.', $e->getMessage());
        }
    }

    private function forged(int $value): BoundedNumber
    {
        return $this->restored($this->payload('s:5:"value";' . \serialize($value)));
    }

    private function payload(string $body, int $count = 1): string
    {
        return \sprintf('O:%d:"%s":%d:{%s}', \strlen(BoundedNumber::class), BoundedNumber::class, $count, $body);
    }

    private function restored(string $payload): BoundedNumber
    {
        $object = \unserialize($payload);
        self::assertInstanceOf(BoundedNumber::class, $object);

        return $object;
    }
}
