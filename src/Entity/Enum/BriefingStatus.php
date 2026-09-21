<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity\Enum;

enum BriefingStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::READY => 'Ready',
            self::DELIVERED => 'Delivered',
        };
    }

    /**
     * Slotted players only see a briefing once staff have marked it Ready (or later).
     */
    public function isVisibleToPlayers(): bool
    {
        return $this !== self::DRAFT;
    }
}
