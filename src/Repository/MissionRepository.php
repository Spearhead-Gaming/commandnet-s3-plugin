<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\Mission;

/**
 * @extends AbstractRepository<Mission>
 */
class MissionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Mission::class;
    }
}
