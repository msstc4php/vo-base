<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

use RuntimeException;

/**
 * Thrown when a value-object operation needs an optional package that is not
 * installed (e.g. arithmetic on {@see \MSSTC4PHP\ValueObject\Finance\Decimal}
 * requires "brick/math"). This is not an invalid-value condition, so it does
 * not extend {@see InvalidValueObjectException}.
 */
final class MissingDependencyException extends RuntimeException
{
}
