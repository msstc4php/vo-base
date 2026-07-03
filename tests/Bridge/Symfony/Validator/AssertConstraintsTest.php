<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Bridge\Symfony\Validator;

use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertEmailAddress;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertIpv4;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertPhoneNumber;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertSlug;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertUrl;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(AssertEmailAddress::class)]
#[CoversClass(AssertIpv4::class)]
#[CoversClass(AssertPhoneNumber::class)]
#[CoversClass(AssertSlug::class)]
#[CoversClass(AssertUrl::class)]
#[CoversClass(AssertUuid::class)]
final class AssertConstraintsTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidator();
    }

    /**
     * @return iterable<string, array{Constraint, string, string}>
     */
    public static function constraints(): iterable
    {
        yield 'email' => [new AssertEmailAddress(), 'user@example.com', 'nope'];
        yield 'ipv4' => [new AssertIpv4(), '192.168.0.1', '999.1.1.1'];
        yield 'phone' => [new AssertPhoneNumber(), '+14155552671', 'abc'];
        yield 'slug' => [new AssertSlug(), 'my-slug', 'Not a Slug!'];
        yield 'url' => [new AssertUrl(), 'https://example.com/a', 'not a url'];
        yield 'uuid' => [new AssertUuid(), '018f8c1e-7b2a-7cde-8f01-2a3b4c5d6e7f', 'not-a-uuid'];
    }

    #[DataProvider('constraints')]
    public function testValidValueHasNoViolations(Constraint $constraint, string $valid, string $invalid): void
    {
        self::assertCount(0, $this->validator->validate($valid, $constraint));
    }

    #[DataProvider('constraints')]
    public function testInvalidValueHasOneViolation(Constraint $constraint, string $valid, string $invalid): void
    {
        self::assertCount(1, $this->validator->validate($invalid, $constraint));
    }
}
