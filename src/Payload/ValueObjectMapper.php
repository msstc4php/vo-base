<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use BackedEnum;
use LogicException;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use ReflectionEnum;

/**
 * The storage form every framework bridge shares: a string, int or float for a
 * scalar-backed value object or a backed enum, a flat array for a composite with
 * a codec. JSON is left to the bridge.
 *
 * Raw values are read the way databases hand them over: an int-backed value
 * object accepts a decimal-digit string, a float-backed one a numeric string,
 * because PDO and most drivers return numbers as strings.
 *
 * @phpstan-type Payload string|int|float|array<string, scalar|null>
 */
final readonly class ValueObjectMapper
{
    /**
     * @var array<class-string<ValueObject>, PayloadCodec<ValueObject>>
     */
    private array $codecs;

    /**
     * @param iterable<PayloadCodec<ValueObject>> $codecs added to the built-in ones; a codec for an already covered class replaces it
     */
    public function __construct(iterable $codecs = [])
    {
        $byClass = [];
        foreach ([new BoundedStringCodec(), new IntRangeCodec(), new GeoCoordinatesCodec(), new DateRangeCodec(), ...$codecs] as $codec) {
            $byClass[$codec->valueObjectClass()] = $codec;
        }

        $this->codecs = $byClass;
    }

    public function supports(string $class): bool
    {
        return isset($this->codecs[$class])
            || is_subclass_of($class, StringConvertible::class)
            || is_subclass_of($class, AbstractIntValueObject::class)
            || is_subclass_of($class, AbstractFloatValueObject::class)
            || is_subclass_of($class, BackedEnum::class);
    }

    /**
     * @return Payload
     *
     * @throws LogicException when the value's class is not supported
     */
    public function toPayload(ValueObject|BackedEnum $value): string|int|float|array
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        $codec = $this->codecs[$value::class] ?? null;
        if ($codec !== null) {
            return $codec->encode($value);
        }

        if ($value instanceof StringConvertible) {
            return (string) $value;
        }

        if ($value instanceof AbstractIntValueObject || $value instanceof AbstractFloatValueObject) {
            return $value->value;
        }

        throw $this->unsupported($value::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws InvalidValueObjectException when the payload is not a valid T; InvalidPayloadException for a wrong type or shape
     * @throws LogicException when $class is not supported
     */
    public function fromPayload(string $class, mixed $payload): object
    {
        $codec = $this->codecs[$class] ?? null;
        $value = match (true) {
            $codec !== null => $codec->decode(\is_array($payload) ? $payload : throw $this->wrongType($class, 'an array', $payload)),
            is_subclass_of($class, StringConvertible::class) => $this->fromString($class, $payload),
            is_subclass_of($class, AbstractIntValueObject::class) => $this->fromInt($class, $payload),
            is_subclass_of($class, AbstractFloatValueObject::class) => $this->fromFloat($class, $payload),
            is_subclass_of($class, BackedEnum::class) => $this->fromEnum($class, $payload),
            default => throw $this->unsupported($class),
        };

        return $value instanceof $class ? $value : throw $this->unsupported($class);
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    private function fromString(string $class, mixed $payload): StringConvertible
    {
        return \is_string($payload) ? $class::from($payload) : throw $this->wrongType($class, 'a string', $payload);
    }

    /**
     * @param class-string<AbstractIntValueObject> $class
     */
    private function fromInt(string $class, mixed $payload): AbstractIntValueObject
    {
        if (\is_string($payload) && preg_match('/^-?\d+$/D', $payload) === 1) {
            $payload = filter_var($payload, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE) ?? $payload;
        }

        return \is_int($payload) ? $class::from($payload) : throw $this->wrongType($class, 'an integer', $payload);
    }

    /**
     * @param class-string<AbstractFloatValueObject> $class
     */
    private function fromFloat(string $class, mixed $payload): AbstractFloatValueObject
    {
        if (\is_string($payload) && is_numeric($payload)) {
            $payload = (float) $payload;
        }

        return \is_int($payload) || \is_float($payload) ? $class::from((float) $payload) : throw $this->wrongType($class, 'a number', $payload);
    }

    /**
     * @param class-string<BackedEnum> $class
     */
    private function fromEnum(string $class, mixed $payload): BackedEnum
    {
        if (new ReflectionEnum($class)->getBackingType()?->getName() === 'int') {
            if (\is_string($payload) && preg_match('/^-?\d+$/D', $payload) === 1) {
                $payload = (int) $payload;
            }

            return \is_int($payload) ? $this->caseOf($class, $payload) : throw $this->wrongType($class, 'an integer', $payload);
        }

        return \is_string($payload) ? $this->caseOf($class, $payload) : throw $this->wrongType($class, 'a string', $payload);
    }

    /**
     * @param class-string<BackedEnum> $class
     */
    private function caseOf(string $class, int|string $payload): BackedEnum
    {
        return $class::tryFrom($payload)
            ?? throw new InvalidPayloadException(\sprintf('"%s" is not a case of %s.', InvalidValueObjectException::excerpt((string) $payload), $class));
    }

    private function wrongType(string $class, string $expected, mixed $payload): InvalidPayloadException
    {
        return new InvalidPayloadException(\sprintf('A %s payload must be %s, %s given.', $class, $expected, get_debug_type($payload)));
    }

    private function unsupported(string $class): LogicException
    {
        return new LogicException(\sprintf('%s is neither a scalar-backed value object, a backed enum nor a class with a payload codec.', $class));
    }
}
