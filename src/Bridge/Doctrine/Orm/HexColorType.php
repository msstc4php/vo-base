<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Format\HexColor;
use Override;

final class HexColorType extends AbstractStringVoType
{
    public const string NAME = 'vo_hex_color';

    protected function valueObjectClass(): string
    {
        return HexColor::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 7;
    }
}
