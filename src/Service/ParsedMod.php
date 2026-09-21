<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNetS3\Entity\Enum\ModKind;

/**
 * One entry read out of a typed list or an uploaded launcher preset. steamId is the Workshop id
 * for a mod (or the store app id for a DLC); null means a local mod nobody can download.
 */
final class ParsedMod
{
    public function __construct(
        public readonly string $name,
        public readonly ModKind $kind,
        public readonly ?string $steamId,
    ) {
    }
}
