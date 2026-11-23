<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Geo;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;

/**
 * A latitude/longitude pair identifying a point on Earth.
 */
final readonly class GeoCoordinates implements ValueObject
{
    public function __construct(
        public Latitude $latitude,
        public Longitude $longitude,
    ) {
    }

    /**
     * @return array{latitude: Latitude, longitude: Longitude}
     */
    public function __serialize(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude];
    }

    /**
     * Runs the constructor's checks again, so a forged or stale payload never yields an instance the constructor
     * would refuse. Other keys are ignored.
     *
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidValueObjectException when a key is missing or of the wrong type, or the values break the type's rules
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'latitude');

        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        if (!$latitude instanceof Latitude || !$longitude instanceof Longitude) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold a Latitude and a Longitude.', self::class));
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public static function fromFloats(float $latitude, float $longitude): self
    {
        return new self(new Latitude($latitude), new Longitude($longitude));
    }

    public function latitude(): Latitude
    {
        return $this->latitude;
    }

    public function longitude(): Longitude
    {
        return $this->longitude;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->latitude->equals($other->latitude)
            && $this->longitude->equals($other->longitude);
    }

    /**
     * @return array{lat: float, lng: float}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude->value,
            'lng' => $this->longitude->value,
        ];
    }
}
