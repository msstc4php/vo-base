<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidPortException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;

/**
 * A TCP/UDP port number in the range 0-65535.
 */
final class Port extends AbstractIntValueObject
{
    protected function parse(int $value): int
    {
        if ($value < 0 || $value > 65535) {
            throw new InvalidPortException(\sprintf('Value "%d" is not a valid port number.', $value));
        }

        return $value;
    }
}
