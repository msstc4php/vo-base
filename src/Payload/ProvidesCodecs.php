<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * The payload codecs a package ships for its composite value objects, so a bridge can register them all from one
 * class name; without a bridge, pass the list to the mapper directly (`new ValueObjectMapper(Codecs::all())` for
 * vo-documents). The codecs are built without dependencies: a codec that needs a service is registered on its own.
 */
interface ProvidesCodecs
{
    /**
     * @return list<PayloadCodec<ValueObject>>
     */
    public static function all(): array;
}
