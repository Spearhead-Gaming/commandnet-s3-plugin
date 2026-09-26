<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Service\OperationPageProvisioner;

/**
 * Gives every new operation its information page as it is saved, whether it was typed in by
 * hand or created by a Deployment's "Generate Operations". Persisting the page here (rather
 * than after the flush) puts both rows in the same flush.
 */
#[AsEntityListener(Events::prePersist, method: 'prePersist', entity: Operation::class)]
class CreateOperationPageListener
{
    public function __construct(private readonly OperationPageProvisioner $provisioner)
    {
    }

    public function prePersist(Operation $operation): void
    {
        if ($this->provisioner->isEligible($operation)) {
            $this->provisioner->provision($operation);
        }
    }
}
