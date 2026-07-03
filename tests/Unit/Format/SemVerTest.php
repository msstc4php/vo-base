<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidSemVerException;
use MSSTC4PHP\ValueObject\Format\SemVer;
use PHPUnit\Framework\Attributes\CoversClass;
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
}
