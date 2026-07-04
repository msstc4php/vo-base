<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Bridge\Symfony\Validator;

use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertBic;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertCidr;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertCreditCardNumber;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertDecimal;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertDuration;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertEmailAddress;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertIban;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertIpv4;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertPhoneNumber;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertSlug;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertTimeOfDay;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertUrl;
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(AssertBic::class)]
#[CoversClass(AssertCidr::class)]
#[CoversClass(AssertCreditCardNumber::class)]
#[CoversClass(AssertDecimal::class)]
#[CoversClass(AssertDuration::class)]
#[CoversClass(AssertEmailAddress::class)]
#[CoversClass(AssertIban::class)]
#[CoversClass(AssertIpv4::class)]
#[CoversClass(AssertPhoneNumber::class)]
#[CoversClass(AssertSlug::class)]
#[CoversClass(AssertTimeOfDay::class)]
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
        yield 'iban' => [new AssertIban(), 'GB82WEST12345698765432', 'not-an-iban'];
        yield 'bic' => [new AssertBic(), 'DEUTDEFF', 'nope'];
        yield 'credit_card_number' => [new AssertCreditCardNumber(), '4111111111111111', '4111111111111112'];
        yield 'decimal' => [new AssertDecimal(), '1.23', 'not-a-number'];
        yield 'cidr' => [new AssertCidr(), '192.168.0.0/24', 'not-a-cidr'];
        yield 'time_of_day' => [new AssertTimeOfDay(), '09:30:00', '25:00:00'];
        yield 'duration' => [new AssertDuration(), 'PT30S', 'not-a-duration'];
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
