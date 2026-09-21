<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity\Enum;

enum AssetCategory: string
{
    case TERRAIN = 'terrain';
    case FACTION = 'faction';
    case VEHICLE = 'vehicle';
    case MISC = 'misc';

    public function label(): string
    {
        return match ($this) {
            self::TERRAIN => 'Terrain',
            self::FACTION => 'Faction',
            self::VEHICLE => 'Vehicle',
            self::MISC => 'Misc',
        };
    }
}
