<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Support\HexOctets;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HexOctets::class)]
final class HexOctetsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, positive-int, 'dotted'|'bare', string|null}>
     */
    public static function provider(): iterable
    {
        yield 'colon' => ['AA:BB:CC', 3, 'bare', 'aa:bb:cc'];
        yield 'hyphen' => ['aa-bb-cc', 3, 'bare', 'aa:bb:cc'];
        yield 'bare' => ['AABBCC', 3, 'bare', 'aa:bb:cc'];
        yield 'bare is not dotted' => ['aabb.cc', 3, 'bare', null];
        yield 'dotted' => ['AABB.CCDD', 4, 'dotted', 'aa:bb:cc:dd'];
        yield 'dotted is not bare' => ['aabbccdd', 4, 'dotted', null];
        yield 'wrong octet count' => ['aa:bb', 3, 'bare', null];
        yield 'mixed separators' => ['aa:bb-cc', 3, 'bare', null];
        yield 'trailing newline' => ["aa:bb:cc\n", 3, 'bare', null];
    }

    /**
     * @param positive-int $octets
     * @param 'dotted'|'bare' $alternative
     */
    #[DataProvider('provider')]
    public function testCanonical(string $value, int $octets, string $alternative, ?string $expected): void
    {
        self::assertSame($expected, HexOctets::canonical($value, $octets, $alternative));
    }
}
