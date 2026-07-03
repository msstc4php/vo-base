<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidSlugException;
use MSSTC4PHP\ValueObject\Format\Slug;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Slug::class)]
final class SlugTest extends TestCase
{
    public function testAcceptsValidSlug(): void
    {
        self::assertSame('my-post-1', Slug::from('my-post-1')->value);
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidSlugException::class);

        Slug::from('My Post');
    }

    public function testRejectsLeadingHyphen(): void
    {
        $this->expectException(InvalidSlugException::class);

        Slug::from('-lead');
    }

    public function testRejectsTrailingHyphen(): void
    {
        $this->expectException(InvalidSlugException::class);

        Slug::from('trail-');
    }
}
