<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\SopVersion;

/**
 * @extends AbstractRepository<SopVersion>
 */
class SopVersionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return SopVersion::class;
    }
}
