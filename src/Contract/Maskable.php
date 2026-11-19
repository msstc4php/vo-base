<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Contract;

/**
 * A value object that holds sensitive data and offers a form safe for logs and screens.
 * {@see \Stringable::__toString()} and serialization still give the full value.
 */
interface Maskable
{
    /** @return non-empty-string */
    public function masked(): string;
}
