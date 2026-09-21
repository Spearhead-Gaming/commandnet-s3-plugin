<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNet\Entity\Equipment;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Repository\MissionKitApprovalRepository;

/**
 * Approval for a piece of equipment on one specific operation. Standing rules already live in
 * Command Net (the weapons each Position may use, the vehicles each Unit fields); this covers
 * the "approved for this mission only" case and feeds the loadout check.
 */
#[ORM\Entity(MissionKitApprovalRepository::class)]
#[ORM\Table(name: 's3_mission_kit_approval')]
#[ORM\UniqueConstraint(name: 's3_mission_kit_unique', columns: ['operation_id', 'equipment_id'])]
class MissionKitApproval implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Operation $operation;

    #[ORM\ManyToOne(targetEntity: Equipment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Equipment $equipment;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(Equipment $equipment): void
    {
        $this->equipment = $equipment;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->operation->getTitle() . ': ' . $this->equipment->getName();
    }
}
