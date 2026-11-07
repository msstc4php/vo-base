<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Testing;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Testing\RandomValueObjects;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use ReflectionClass;
use ReflectionMethod;
use UnitEnum;

/**
 * Generated values are built through each type's validating constructor, so a
 * thousand draws per generator without an exception is the property under test.
 */
#[UsesClass(InvalidValueObjectException::class)]
#[CoversClass(RandomValueObjects::class)]
#[UsesNamespace('MSSTC4PHP\\ValueObject')]
final class RandomValueObjectsTest extends TestCase
{
    /**
     * @return list<non-empty-string>
     */
    public static function generatorNames(): array
    {
        $names = [];
        foreach (new ReflectionClass(RandomValueObjects::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !$method->isStatic() && $method->getNumberOfRequiredParameters() === 0) {
                $names[] = $method->getName();
            }
        }

        \sort($names);

        return $names;
    }

    /**
     * @return iterable<string, array{non-empty-string, int}>
     */
    public static function generatorProvider(): iterable
    {
        foreach (self::generatorNames() as $name) {
            foreach ([1, 42, 2026] as $seed) {
                yield $name . ' seed ' . $seed => [$name, $seed];
            }
        }
    }

    #[DataProvider('generatorProvider')]
    public function testEveryDrawIsAValidValueObject(string $method, int $seed): void
    {
        $random = new RandomValueObjects($seed);
        $generator = new ReflectionMethod(RandomValueObjects::class, $method);

        for ($i = 0; $i < 400; $i++) {
            $value = $generator->invoke($random);
            self::assertTrue($value instanceof ValueObject || $value instanceof UnitEnum, \sprintf('%s, seed %d, draw %d', $method, $seed, $i));
        }
    }

    public function testTheGeneratorSetIsDeliberate(): void
    {
        self::assertSame([
            'base64String', 'bic', 'boundedString', 'cidr', 'countryCode', 'creditCardNumber', 'currencyCode',
            'dateRange', 'decimal', 'domainName', 'duration', 'emailAddress', 'geoCoordinates', 'hexColor',
            'hostname', 'httpMethod', 'iban', 'intRange', 'ipAddress', 'ipRange', 'ipv4Address', 'ipv6Address',
            'jsonString', 'languageCode', 'latitude', 'locale', 'longitude', 'macAddress', 'nonEmptyString',
            'nonNegativeInt', 'percentage', 'phoneNumber', 'port', 'positiveInt', 'queryString', 'semVer', 'slug',
            'timeOfDay', 'uri', 'url', 'urlPath', 'urlScheme', 'uuid',
        ], self::generatorNames());
    }

    public function testAnUnseededRunStillProducesValidValues(): void
    {
        $random = new RandomValueObjects();

        for ($i = 0; $i < 200; $i++) {
            self::assertTrue(Url::isValid($random->url()->value));
        }
    }

    public function testACustomRandomizerIsUsed(): void
    {
        $a = new RandomValueObjects(new Randomizer(new Xoshiro256StarStar(9)));
        $b = new RandomValueObjects(9);

        self::assertSame($a->uuid()->value, $b->uuid()->value);
    }

    public function testHostnamesNeverLookLikeIpv4(): void
    {
        $random = new RandomValueObjects(5);

        for ($i = 0; $i < 2000; $i++) {
            $labels = \explode('.', $random->hostname()->value);
            self::assertMatchesRegularExpression('/^[a-z]/', $labels[\count($labels) - 1]);
        }
    }

    public function testASeedReproducesTheSequence(): void
    {
        $a = new RandomValueObjects(7);
        $b = new RandomValueObjects(7);

        for ($i = 0; $i < 50; $i++) {
            self::assertSame($a->ipRange()->value, $b->ipRange()->value);
            self::assertSame($a->iban()->value, $b->iban()->value);
            self::assertSame($a->uuid()->value, $b->uuid()->value);
        }
    }

    public function testWithoutASeedRunsDiffer(): void
    {
        self::assertNotSame(new RandomValueObjects()->uuid()->value, new RandomValueObjects()->uuid()->value);
    }

    public function testCurrenciesCanBeMappedAsMoney(): void
    {
        $random = new RandomValueObjects(1);

        for ($i = 0; $i < 500; $i++) {
            self::assertNotNull($random->currencyCode()->isoMinorUnits());
        }
    }

    public function testShapesOfTheGeneratedValues(): void
    {
        $random = new RandomValueObjects(2026);
        $versions = [];
        $rangeVersions = [];
        $prefixes = [];
        $labels = [];
        $schemes = [];

        for ($i = 0; $i < 2000; $i++) {
            self::assertSame(4, IpAddress::fromString($random->ipv4Address()->value)->version());
            self::assertSame(6, IpAddress::fromString($random->ipv6Address()->value)->version());
            $versions[$random->ipAddress()->version()] = true;
            $rangeVersions[$random->ipRange()->version()] = true;

            $cidr = $random->cidr();
            $prefixes[$cidr->version()][$cidr->prefixLength()] = true;

            $labels[\substr_count($random->hostname()->value, '.') + 1] = true;
            $schemes[\strtok($random->url()->value, ':')] = true;

            $uuid = $random->uuid()->value;
            self::assertSame('4', $uuid[14]);
            self::assertContains($uuid[19], ['8', '9', 'a', 'b']);

            $card = $random->creditCardNumber();
            self::assertSame(16, \strlen($card->value));
            self::assertSame('visa', $card->brand());

            self::assertStringStartsWith('DE', $random->iban()->value);
            self::assertMatchesRegularExpression('/^[A-Z]{4}DE[A-Z0-9][A-NP-Z1-9]$/D', $random->bic()->value);
            self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/D', $random->timeOfDay()->value);

            $range = $random->dateRange();
            self::assertSame('UTC', $range->start()->getTimezone()->getName());
            self::assertGreaterThanOrEqual(1_577_836_800, $range->start()->getTimestamp());
            self::assertLessThanOrEqual(1_893_456_000, $range->start()->getTimestamp());
            self::assertLessThanOrEqual(365 * 86_400, $range->end()->getTimestamp() - $range->start()->getTimestamp());

            $percentage = $random->percentage()->value;
            self::assertGreaterThanOrEqual(0.0, $percentage);
            self::assertLessThanOrEqual(100.0, $percentage);
            self::assertGreaterThan(0, $random->positiveInt()->value);
            self::assertGreaterThanOrEqual(0, $random->nonNegativeInt()->value);
            self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-(alpha|beta|rc)\.\d)?(\+[a-z0-9]+)?$/D', $random->semVer()->value);
        }

        self::assertSame([4 => true, 6 => true], $versions + [4 => false, 6 => false]);
        self::assertSame([4 => true, 6 => true], $rangeVersions + [4 => false, 6 => false]);
        self::assertArrayHasKey(4, $prefixes);
        self::assertArrayHasKey(6, $prefixes);
        self::assertArrayHasKey(0, $prefixes[4]);
        self::assertArrayHasKey(32, $prefixes[4]);
        self::assertArrayHasKey(0, $prefixes[6]);
        self::assertArrayHasKey(128, $prefixes[6]);
        $labelCounts = \array_keys($labels);
        \sort($labelCounts);
        self::assertSame([1, 2, 3], $labelCounts);
        self::assertEqualsCanonicalizing(['http', 'https'], \array_keys($schemes));
    }

    /**
     * Pins every generator's draws: a change to any of them changes the digest,
     * so the CHANGELOG can say that seeded sequences changed.
     */
    public function testGoldenSequenceForSeed42(): void
    {
        $random = new RandomValueObjects(42);
        $draws = [];
        foreach (self::generatorNames() as $name) {
            $value = new ReflectionMethod(RandomValueObjects::class, $name)->invoke($random);
            $draws[] = $name . '=' . \json_encode($value, \JSON_THROW_ON_ERROR);
        }

        self::assertSame('17d2ff31f7a50a6804b4e5da6e249d0c3d9ce4710ea80b314b58754a9f96ce26', \hash('sha256', \implode("\n", $draws)));
    }
}
