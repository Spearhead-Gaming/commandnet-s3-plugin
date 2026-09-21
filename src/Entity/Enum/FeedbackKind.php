<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity\Enum;

enum FeedbackKind: string
{
    case BUG = 'bug';
    case BALANCE = 'balance';
    case FEEDBACK = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::BUG => 'Bug',
            self::BALANCE => 'Balance issue',
            self::FEEDBACK => 'General feedback',
        };
    }
}
