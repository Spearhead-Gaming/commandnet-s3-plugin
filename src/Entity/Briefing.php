<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\Enum\BriefingStatus;
use MajesticDev\CommandNetS3\Repository\BriefingRepository;

/**
 * The structured briefing for an operation. Command Net's Operation already holds a free-form
 * OPORD body; this adds the parts it lacks (ordered objectives, a Draft/Ready/Delivered
 * status that gates player visibility). One briefing per operation.
 */
#[ORM\Entity(BriefingRepository::class)]
#[ORM\Table(name: 's3_briefing')]
class Briefing
{
    use IdentifiableEntityTrait;

    #[ORM\OneToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Operation $operation;

    #[ORM\Column(length: 255)]
    private string $missionName = '';

    #[ORM\Column(type: 'text')]
    private string $taskPurpose = '';

    /**
     * One objective per line, in priority order.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $objectives = null;

    #[ORM\Column(length: 20, enumType: BriefingStatus::class)]
    private BriefingStatus $status = BriefingStatus::DRAFT;

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    public function getMissionName(): string
    {
        return $this->missionName;
    }

    public function setMissionName(string $missionName): void
    {
        $this->missionName = $missionName;
    }

    public function getTaskPurpose(): string
    {
        return $this->taskPurpose;
    }

    public function setTaskPurpose(string $taskPurpose): void
    {
        $this->taskPurpose = $taskPurpose;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function setObjectives(?string $objectives): void
    {
        $this->objectives = $objectives;
    }

    /**
     * @return list<string>
     */
    public function getObjectiveList(): array
    {
        $lines = preg_split('/\R/', $this->objectives ?? '') ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $l): bool => $l !== ''));
    }

    public function getStatus(): BriefingStatus
    {
        return $this->status;
    }

    public function setStatus(BriefingStatus $status): void
    {
        $this->status = $status;
    }
}
