<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\ZeusAsset;

/**
 * @extends AbstractRepository<ZeusAsset>
 */
class ZeusAssetRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return ZeusAsset::class;
    }
}
