<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\MissionMod;

/**
 * @extends AbstractRepository<MissionMod>
 */
class MissionModRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return MissionMod::class;
    }
}
