<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNet\Entity\Enum\OperationStatus;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNet\Repository\OperationRepository;

/**
 * Which operation the "current operation" page shows: the one running now, otherwise the next
 * one coming up.
 */
class CurrentOperationFinder
{
    public function __construct(private readonly OperationRepository $operationRepository)
    {
    }

    public function find(): ?Operation
    {
        $running = $this->operationRepository->findOneBy(
            ['status' => OperationStatus::IN_PROGRESS],
            ['startDateTime' => 'DESC'],
        );
        if ($running !== null) {
            return $running;
        }

        // A day of grace, so an operation that started late last night isn't already "gone".
        /** @var Operation|null */
        return $this->operationRepository->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.startDateTime >= :since')
            ->setParameter('status', OperationStatus::SCHEDULED->value)
            ->setParameter('since', new \DateTime('-1 day'))
            ->orderBy('o.startDateTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
