<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Entity\User;
use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNet\Entity\Enum\SoldierStatus;
use MajesticDev\CommandNet\Entity\SoldierProfile;
use MajesticDev\CommandNetS3\Entity\SopAcknowledgement;
use MajesticDev\CommandNetS3\Entity\SopVersion;

/**
 * @extends AbstractRepository<SopAcknowledgement>
 */
class SopAcknowledgementRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return SopAcknowledgement::class;
    }

    public function hasAcknowledged(SopVersion $version, User $user): bool
    {
        return $this->count(['sopVersion' => $version, 'user' => $user]) > 0;
    }

    /**
     * Enlisted members who haven't acknowledged the given version yet.
     *
     * @return list<SoldierProfile>
     */
    public function findPendingSoldiers(SopVersion $version): array
    {
        $dql = sprintf(
            'SELECT s FROM %s s WHERE s.status NOT IN (:out) AND NOT EXISTS ('
            . 'SELECT 1 FROM %s a WHERE a.sopVersion = :version AND a.user = s.user) ORDER BY s.id',
            SoldierProfile::class,
            SopAcknowledgement::class,
        );

        /** @var list<SoldierProfile> */
        return $this->getEntityManager()->createQuery($dql)
            ->setParameter('out', [SoldierStatus::DISCHARGED->value, SoldierStatus::RETIRED->value])
            ->setParameter('version', $version)
            ->getResult();
    }
}
