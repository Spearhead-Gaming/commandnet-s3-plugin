<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Repository;

use Forumify\Core\Repository\AbstractRepository;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\OperationPage;

/**
 * @extends AbstractRepository<OperationPage>
 */
class OperationPageRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return OperationPage::class;
    }

    /**
     * The highest sequence already used in order numbers shaped "<prefix>-<n>", among the
     * Deployment's operations, or among all pages when there is no Deployment. Numbers a person
     * typed in another shape ("OP-2026-014") are ignored.
     */
    public function highestSequence(?Deployment $deployment, string $prefix): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.orderNumber')
            ->where('p.orderNumber LIKE :like')
            ->setParameter('like', $prefix . '-%');

        if ($deployment !== null) {
            if ($deployment->getId() === null) {
                return 0; // not saved yet, so nothing can belong to it
            }
            $qb->join('p.operation', 'o')
                ->andWhere('o.deployment = :deployment')
                ->setParameter('deployment', $deployment);
        }

        $highest = 0;
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string)$row['orderNumber'], $match) === 1) {
                $highest = max($highest, (int)$match[1]);
            }
        }

        return $highest;
    }
}
