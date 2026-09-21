<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\MissionFeedback;

/**
 * @extends AbstractRepository<MissionFeedback>
 */
class MissionFeedbackRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return MissionFeedback::class;
    }
}
