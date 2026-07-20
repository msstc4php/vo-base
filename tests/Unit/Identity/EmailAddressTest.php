<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Identity;

use MSSTC4PHP\ValueObject\Exception\InvalidEmailAddressException;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailAddress::class)]
final class EmailAddressTest extends TestCase
{
    public function testConstructsFromValidAddress(): void
    {
        self::assertSame('a@b.com', EmailAddress::from('a@b.com')->value);
    }

    public function testSplitsIntoLocalAndDomain(): void
    {
        $email = EmailAddress::from('a@b.com');

        self::assertSame('a', $email->local());
        self::assertSame('b.com', $email->domain());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAddressProvider(): iterable
    {
        yield 'no at' => ['no-at'];
        yield 'missing domain' => ['a@'];
        yield 'missing local' => ['@b.com'];
    }

    #[DataProvider('invalidAddressProvider')]
    public function testRejectsInvalidAddress(string $value): void
    {
        $this->expectException(InvalidEmailAddressException::class);

        EmailAddress::from($value);
    }

    public function testDomainNameForRegistrableDomain(): void
    {
        $domain = EmailAddress::from('user@example.com')->domainName();

        self::assertNotNull($domain);
        self::assertSame('example.com', $domain->value);
    }

    public function testDomainNameNullForNonRegistrableDomain(): void
    {
        // Valid address, but "example.c" is not a registrable domain (1-char TLD).
        self::assertNull(EmailAddress::from('user@example.c')->domainName());
    }
}
