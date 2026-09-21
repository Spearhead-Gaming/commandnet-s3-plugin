<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\MissionKitApproval;

/**
 * @extends AbstractRepository<MissionKitApproval>
 */
class MissionKitApprovalRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return MissionKitApproval::class;
    }
}
