<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNetS3\Entity\DeploymentPage;

/**
 * @extends AbstractRepository<DeploymentPage>
 */
class DeploymentPageRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return DeploymentPage::class;
    }
}
