<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\ModPackMod;

/**
 * @extends AbstractRepository<ModPackMod>
 */
class ModPackModRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return ModPackMod::class;
    }
}
