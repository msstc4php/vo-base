<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidSemVerException;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SemVer::class)]
final class SemVerTest extends TestCase
{
    public function testParsesAllComponents(): void
    {
        $version = SemVer::from('1.2.3-rc.1+build');

        self::assertSame(1, $version->major());
        self::assertSame(2, $version->minor());
        self::assertSame(3, $version->patch());
        self::assertSame('rc.1', $version->prerelease());
        self::assertSame('build', $version->build());
    }

    public function testPlainVersionHasNoPrereleaseOrBuild(): void
    {
        $version = SemVer::from('1.0.0');

        self::assertNull($version->prerelease());
        self::assertNull($version->build());
    }

    public function testComparesByPatch(): void
    {
        self::assertSame(-1, SemVer::from('1.0.0')->compareTo(SemVer::from('1.0.1')));
    }

    public function testPrereleaseIsLowerThanRelease(): void
    {
        self::assertSame(-1, SemVer::from('1.0.0-rc')->compareTo(SemVer::from('1.0.0')));
    }

    public function testBuildMetadataIsIgnoredInComparison(): void
    {
        self::assertSame(0, SemVer::from('1.0.0+a')->compareTo(SemVer::from('1.0.0+b')));
    }

    public function testRejectsIncompleteVersion(): void
    {
        $this->expectException(InvalidSemVerException::class);

        SemVer::from('1.2');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function precedenceChain(): iterable
    {
        // semver.org 2.0.0 example ordering of ascending prerelease precedence.
        yield 'alpha < alpha.1' => ['1.0.0-alpha', '1.0.0-alpha.1'];
        yield 'alpha.1 < alpha.beta' => ['1.0.0-alpha.1', '1.0.0-alpha.beta'];
        yield 'alpha.beta < beta' => ['1.0.0-alpha.beta', '1.0.0-beta'];
        yield 'beta < beta.2' => ['1.0.0-beta', '1.0.0-beta.2'];
        yield 'beta.2 < beta.11' => ['1.0.0-beta.2', '1.0.0-beta.11'];
        yield 'beta.11 < rc.1' => ['1.0.0-beta.11', '1.0.0-rc.1'];
        yield 'rc.1 < release' => ['1.0.0-rc.1', '1.0.0'];
    }

    #[DataProvider('precedenceChain')]
    public function testPrereleasePrecedenceOrdering(string $lower, string $higher): void
    {
        self::assertSame(-1, SemVer::from($lower)->compareTo(SemVer::from($higher)));
        self::assertSame(1, SemVer::from($higher)->compareTo(SemVer::from($lower)));
    }

    public function testNumericIdentifierRanksBelowAlphanumeric(): void
    {
        self::assertSame(-1, SemVer::from('1.0.0-1')->compareTo(SemVer::from('1.0.0-alpha')));
    }

    public function testBuildMetadataIsIgnoredButExposed(): void
    {
        self::assertSame('a', SemVer::from('1.0.0+a')->build());
        self::assertSame(0, SemVer::from('1.0.0+a')->compareTo(SemVer::from('1.0.0+b')));
    }

    public function testEqualPrereleasesCompareEqual(): void
    {
        self::assertSame(0, SemVer::from('1.0.0-rc.1')->compareTo(SemVer::from('1.0.0-rc.1')));
    }

    public function testCompareToRejectsNonSemVer(): void
    {
        $this->expectException(InvalidSemVerException::class);

        SemVer::from('1.0.0')->compareTo(Percentage::from(50.0));
    }

    public function testIsStable(): void
    {
        self::assertTrue(SemVer::from('1.0.0')->isStable());
        self::assertFalse(SemVer::from('0.9.0')->isStable());
        self::assertFalse(SemVer::from('1.0.0-alpha')->isStable());
    }

    public function testBumpMethods(): void
    {
        self::assertSame('2.0.0', SemVer::from('1.2.3')->nextMajor()->value);
        self::assertSame('1.3.0', SemVer::from('1.2.3')->nextMinor()->value);
        self::assertSame('1.2.4', SemVer::from('1.2.3')->nextPatch()->value);
    }

    public function testBumpDropsPrerelease(): void
    {
        self::assertSame('1.2.4', SemVer::from('1.2.3-rc.1')->nextPatch()->value);
    }
}
