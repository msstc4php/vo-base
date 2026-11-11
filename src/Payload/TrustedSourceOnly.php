<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

/**
 * Marks a codec whose payload carries the value object's own constraints
 * (BoundedString's length limits): decoding it from request input would let the
 * client choose them. ValueObjectMapper refuses such classes on its untrusted
 * entry points, so every bridge applies the rule without knowing the classes.
 */
interface TrustedSourceOnly
{
}
