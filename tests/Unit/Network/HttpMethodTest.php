<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Network\HttpMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(HttpMethod::class)]
final class HttpMethodTest extends TestCase
{
    public function testFrom(): void
    {
        self::assertSame(HttpMethod::GET, HttpMethod::from('GET'));
    }

    /**
     * @return iterable<string, array{HttpMethod, bool, bool}>
     */
    public static function truthTableProvider(): iterable
    {
        yield 'GET' => [HttpMethod::GET, true, true];
        yield 'POST' => [HttpMethod::POST, false, false];
        yield 'PUT' => [HttpMethod::PUT, false, true];
        yield 'DELETE' => [HttpMethod::DELETE, false, true];
    }

    #[DataProvider('truthTableProvider')]
    public function testSafetyAndIdempotency(HttpMethod $method, bool $safe, bool $idempotent): void
    {
        self::assertSame($safe, $method->isSafe());
        self::assertSame($idempotent, $method->isIdempotent());
    }

    public function testUnknownMethodThrows(): void
    {
        $this->expectException(ValueError::class);

        HttpMethod::from('FOO');
    }
}
