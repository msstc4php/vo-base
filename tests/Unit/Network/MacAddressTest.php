<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidMacAddressException;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MacAddress::class)]
final class MacAddressTest extends TestCase
{
    public function testNormalizesColonNotationToLowercase(): void
    {
        self::assertSame('aa:bb:cc:dd:ee:ff', MacAddress::from('AA:BB:CC:DD:EE:FF')->value);
    }

    public function testAllNotationsAreEqualAfterNormalization(): void
    {
        $colon = MacAddress::from('AA:BB:CC:DD:EE:FF');
        $hyphen = MacAddress::from('aa-bb-cc-dd-ee-ff');
        $dotted = MacAddress::from('aabb.ccdd.eeff');

        self::assertTrue($colon->equals($hyphen));
        self::assertTrue($hyphen->equals($dotted));
        self::assertTrue($dotted->equals($colon));
    }

    public function testTooFewOctetsThrows(): void
    {
        $this->expectException(InvalidMacAddressException::class);

        MacAddress::from('AA:BB:CC:DD:EE');
    }
}
