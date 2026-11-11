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
 * object or enum accepts a canonical decimal-digit string, a float-backed one a
 * plain decimal string, because PDO and most drivers return numbers as strings.
 * The untrusted path keeps that on purpose: form fields, XML and CSV carry
 * numbers as strings too.
 *
 * Two entry points by trust: fromPayload() for storage the application wrote,
 * fromUntrustedPayload() for request input, which refuses classes whose codec is
 * {@see TrustedSourceOnly}. Bridges ask supportsUntrusted() before offering a
 * class on an input path.
 *
 * @phpstan-type Payload string|int|float|array<string, scalar|null>
 */
final readonly class ValueObjectMapper
{
    private const string INTEGER = '/^-?(?:0|[1-9]\d*)$/D';

    private const string DECIMAL = '/^-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][-+]?\d+)?$/D';

    /**
     * @var array<class-string<ValueObject>, PayloadCodec<ValueObject>>
     */
    private array $codecs;

    /**
     * @param iterable<array-key, PayloadCodec<ValueObject>> $codecs added to the built-in ones; a codec for an already covered class replaces it
     */
    public function __construct(iterable $codecs = [])
    {
        $byClass = [];
        foreach ([new BoundedStringCodec(), new IntRangeCodec(), new GeoCoordinatesCodec(), new DateRangeCodec()] as $codec) {
            $byClass[$codec->valueObjectClass()] = $codec;
        }

        foreach ($codecs as $codec) {
            $byClass[$codec->valueObjectClass()] = $codec;
        }

        $this->codecs = $byClass;
    }

    public function supports(string $class): bool
    {
        return isset($this->codecs[$class]) || $this->isScalarBacked($class) || is_subclass_of($class, BackedEnum::class);
    }

    /**
     * Whether request input may become a $class: everything supported except a class whose codec is trusted-source only.
     */
    public function supportsUntrusted(string $class): bool
    {
        return $this->supports($class) && !($this->codecs[$class] ?? null) instanceof TrustedSourceOnly;
    }

    /**
     * A class stored through a codec as a flat array (a JSON column, a sub-document).
     */
    public function isComposite(string $class): bool
    {
        return isset($this->codecs[$class]);
    }

    /**
     * A value object whose payload is a single string, int or float: what a URL segment or a form field can carry.
     * Excludes enums and composites.
     *
     * @phpstan-assert-if-true class-string<ValueObject> $class
     */
    public function isScalarBacked(string $class): bool
    {
        return !isset($this->codecs[$class])
            && ((is_subclass_of($class, StringConvertible::class) && is_subclass_of($class, ValueObject::class))
                || is_subclass_of($class, AbstractIntValueObject::class)
                || is_subclass_of($class, AbstractFloatValueObject::class));
    }

    /**
     * The JSON type a $class payload has, for error messages that must not name internal classes.
     *
     * @return 'string'|'int'|'float'|'array'
     *
     * @throws LogicException when $class is not supported
     */
    public function payloadType(string $class): string
    {
        return match (true) {
            isset($this->codecs[$class]) => 'array',
            is_subclass_of($class, AbstractIntValueObject::class) => 'int',
            is_subclass_of($class, AbstractFloatValueObject::class) => 'float',
            $this->isScalarBacked($class) => 'string',
            is_subclass_of($class, BackedEnum::class) => $this->isIntBacked($class) ? 'int' : 'string',
            default => throw $this->unsupported($class),
        };
    }

    /**
     * @return Payload
     *
     * @throws LogicException when the value's class is not supported
     */
    public function toPayload(ValueObject|BackedEnum $value): string|int|float|array
    {
        $codec = $this->codecs[$value::class] ?? null;
        if ($codec !== null && $value instanceof ValueObject) {
            return $codec->encode($value);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
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
     * For storage the application wrote. Request input goes through {@see fromUntrustedPayload()}.
     *
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
            is_subclass_of($class, StringConvertible::class) && is_subclass_of($class, ValueObject::class) => $this->fromString($class, $payload),
            is_subclass_of($class, AbstractIntValueObject::class) => $this->fromInt($class, $payload),
            is_subclass_of($class, AbstractFloatValueObject::class) => $this->fromFloat($class, $payload),
            is_subclass_of($class, BackedEnum::class) => $this->fromEnum($class, $payload),
            default => throw $this->unsupported($class),
        };

        if (!$value instanceof $class) {
            throw new LogicException(\sprintf('The payload codec for %s returned %s.', $class, $value::class));
        }

        return $value;
    }

    /**
     * For request input and raw values mass-assigned into a model: refuses a class whose payload carries its own
     * constraints. That refusal is about the input, so it is an InvalidPayloadException (a client error), not a
     * LogicException; a bridge that should never offer the class asks supportsUntrusted() at configuration time.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws InvalidValueObjectException when the payload is not a valid T; InvalidPayloadException for a wrong type or
     *                                     shape, or for a class that cannot be read from untrusted input
     * @throws LogicException when $class is not supported at all
     */
    public function fromUntrustedPayload(string $class, mixed $payload): object
    {
        if (!$this->supports($class)) {
            throw $this->unsupported($class);
        }

        if (!$this->supportsUntrusted($class)) {
            throw new InvalidPayloadException(\sprintf('A %s cannot be read from untrusted input: its payload carries its own constraints.', $class));
        }

        return $this->fromPayload($class, $payload);
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
        $int = $this->integer($payload);

        return $int !== null ? $class::from($int) : throw $this->wrongType($class, 'an integer', $payload);
    }

    /**
     * @param class-string<AbstractFloatValueObject> $class
     */
    private function fromFloat(string $class, mixed $payload): AbstractFloatValueObject
    {
        $float = match (true) {
            \is_int($payload), \is_float($payload) => (float) $payload,
            \is_string($payload) && preg_match(self::DECIMAL, $payload) === 1 => (float) $payload,
            default => null,
        };

        return $float !== null && is_finite($float) ? $class::from($float) : throw $this->wrongType($class, 'a finite number', $payload);
    }

    /**
     * @param class-string<BackedEnum> $class
     */
    private function fromEnum(string $class, mixed $payload): BackedEnum
    {
        if ($this->isIntBacked($class)) {
            $int = $this->integer($payload);

            return $int !== null ? $this->caseOf($class, $int) : throw $this->wrongType($class, 'an integer', $payload);
        }

        return \is_string($payload) ? $this->caseOf($class, $payload) : throw $this->wrongType($class, 'a string', $payload);
    }

    /**
     * @param class-string<BackedEnum> $class
     */
    private function isIntBacked(string $class): bool
    {
        return new ReflectionEnum($class)->getBackingType()?->getName() === 'int';
    }

    /**
     * An int, or a canonical decimal string within the int range ("007", "1e3", "80\n" are not).
     */
    private function integer(mixed $payload): ?int
    {
        if (\is_int($payload)) {
            return $payload;
        }

        if (!\is_string($payload) || preg_match(self::INTEGER, $payload) !== 1) {
            return null;
        }

        $int = (int) $payload;

        return (string) $int === $payload ? $int : null;
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
