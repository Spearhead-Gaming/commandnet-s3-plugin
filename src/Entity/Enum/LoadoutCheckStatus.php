<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity\Enum;

enum LoadoutCheckStatus: string
{
    case APPROVED = 'approved';
    case NOT_AUTHORIZED = 'not_authorized';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::NOT_AUTHORIZED => 'Not authorized',
            self::UNKNOWN => 'Not in equipment list',
        };
    }
}
