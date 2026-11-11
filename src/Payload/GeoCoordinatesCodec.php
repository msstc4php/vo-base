<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use Override;

/**
 * {lat: float, lng: float}.
 *
 * @implements PayloadCodec<GeoCoordinates>
 */
final readonly class GeoCoordinatesCodec implements PayloadCodec
{
    #[Override]
    public function valueObjectClass(): string
    {
        return GeoCoordinates::class;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof GeoCoordinates) {
            throw new LogicException(\sprintf('%s encodes %s, %s given.', self::class, GeoCoordinates::class, $value::class));
        }

        return $value->toArray();
    }

    #[Override]
    public function decode(array $payload): GeoCoordinates
    {
        $lat = $payload['lat'] ?? null;
        $lng = $payload['lng'] ?? null;
        if (!\is_int($lat) && !\is_float($lat) || !\is_int($lng) && !\is_float($lng)) {
            throw new InvalidPayloadException('Expected a geo-coordinates payload with numeric "lat" and "lng".');
        }

        return GeoCoordinates::fromFloats((float) $lat, (float) $lng);
    }
}
