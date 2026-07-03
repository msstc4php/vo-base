<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Identity;

use MSSTC4PHP\ValueObject\Exception\InvalidEmailAddressException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An e-mail address validated by {@see FILTER_VALIDATE_EMAIL}. No DNS lookup is
 * performed; the raw input is stored verbatim once it passes syntactic validation.
 */
final class EmailAddress extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailAddressException(\sprintf('Value "%s" is not a valid e-mail address.', $value));
        }

        return $value;
    }

    /**
     * The part before the last "@".
     */
    public function local(): string
    {
        return \substr($this->value, 0, $this->atPosition());
    }

    /**
     * The part after the last "@".
     */
    public function domain(): string
    {
        return \substr($this->value, $this->atPosition() + 1);
    }

    /**
     * @return non-negative-int
     */
    private function atPosition(): int
    {
        // Guaranteed present: a validated address always contains "@".
        $position = \strrpos($this->value, '@');

        return $position === false ? 0 : \max(0, $position);
    }
}
