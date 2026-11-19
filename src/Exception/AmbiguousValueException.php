<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * Thrown when a factory finds that a value fits several types and refuses to pick one.
 * The message names the type and the candidates, never the input.
 */
final class AmbiguousValueException extends InvalidValueObjectException
{
    /**
     * @param non-empty-string $type what the factory was asked to build
     * @param non-empty-list<class-string<ValueObject>> $candidates the types the value fits, in the factory's order
     */
    public function __construct(
        string $type,
        private readonly array $candidates,
    ) {
        $names = \array_map(static fn (string $class): string => \basename(\str_replace('\\', '/', $class)), $candidates);

        parent::__construct(\sprintf('Ambiguous %s: the value fits %s.', $type, \implode(', ', $names)));
    }

    /**
     * @return non-empty-list<class-string<ValueObject>>
     */
    public function candidates(): array
    {
        return $this->candidates;
    }
}
