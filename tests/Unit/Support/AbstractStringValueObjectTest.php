<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use MSSTC4PHP\ValueObject\Tests\Fixtures\LowercaseWord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(FreshInstance::class)]
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

    public function testSerializationRoundTripsTheValueAlone(): void
    {
        $original = LowercaseWord::from('hello');
        $payload = \serialize($original);

        self::assertSame($this->payload('s:5:"value";' . \serialize('hello')), $payload);
        self::assertTrue($original->equals($this->restored($payload)));
    }

    public function testAForgedInvalidPayloadIsRefused(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        $this->forged('');
    }

    public function testUnserializeStoresTheCanonicalForm(): void
    {
        self::assertSame('hello', $this->forged('  HeLLo ')->value);
    }

    public function testAPayloadWithoutAStringValueIsRefusedWithoutEchoingIt(): void
    {
        foreach ([\serialize(42), 'N;'] as $raw) {
            $this->assertRefusedWithoutEcho('s:5:"value";' . $raw);
        }
    }

    public function testAPayloadWithoutAValueKeyIsRefused(): void
    {
        $this->assertRefusedWithoutEcho('s:5:"other";' . \serialize('hello'));
    }

    public function testExtraPayloadKeysAreIgnored(): void
    {
        $payload = $this->payload('s:5:"value";' . \serialize('hello') . 's:5:"extra";i:1;', 2);

        self::assertTrue(LowercaseWord::from('hello')->equals($this->restored($payload)));
    }

    public function testAPayloadWithAPrivatePropertyOfASubclassStillUnserializes(): void
    {
        $key = "\0" . LowercaseWord::class . "\0cache";
        $payload = $this->payload('s:5:"value";' . \serialize('hello') . \serialize($key) . 'i:1;', 2);

        self::assertTrue(LowercaseWord::from('hello')->equals($this->restored($payload)));
    }

    public function testUnserializeRefusesAnInstanceThatHoldsAValue(): void
    {
        $this->expectException(LogicException::class);

        LowercaseWord::from('hello')->__unserialize(['value' => 'hello']);
    }

    private function assertRefusedWithoutEcho(string $body): void
    {
        try {
            \unserialize($this->payload($body));
            self::fail('Expected an exception.');
        } catch (InvalidValueObjectException $e) {
            self::assertSame('A serialized ' . LowercaseWord::class . ' must hold its value as a string.', $e->getMessage());
        }
    }

    private function forged(string $value): LowercaseWord
    {
        return $this->restored($this->payload('s:5:"value";' . \serialize($value)));
    }

    private function payload(string $body, int $count = 1): string
    {
        return \sprintf('O:%d:"%s":%d:{%s}', \strlen(LowercaseWord::class), LowercaseWord::class, $count, $body);
    }

    private function restored(string $payload): LowercaseWord
    {
        $object = \unserialize($payload);
        self::assertInstanceOf(LowercaseWord::class, $object);

        return $object;
    }
}
