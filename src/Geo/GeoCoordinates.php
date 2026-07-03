<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Geo;

use MSSTC4PHP\ValueObject\Contract\ValueObject;

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

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->latitude->equals($other->latitude)
            && $this->longitude->equals($other->longitude);
    }

    /**
     * @return array{lat: float, lng: float}
     */
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
