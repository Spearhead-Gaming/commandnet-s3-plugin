<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Repository\MissionVersionRepository;

/**
 * One uploaded build of a mission. The file lives in private storage under a generated name
 * (see MissionStorage); the name it was uploaded with is kept only to name the download.
 */
#[ORM\Entity(MissionVersionRepository::class)]
#[ORM\Table(name: 's3_mission_version')]
class MissionVersion implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Mission $mission;

    #[ORM\Column(length: 50)]
    private string $label = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255)]
    private string $originalName = '';

    #[ORM\Column(length: 64)]
    private string $storedName = '';

    #[ORM\Column(type: 'bigint')]
    private int $size = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $uploadedBy = null;

    /**
     * Unguessable id for the playtest feedback link, so testers reach one version only by being
     * given its link and cannot walk through mission names by counting ids.
     */
    #[ORM\Column(length: 32, unique: true)]
    private string $feedbackToken;

    public function __construct()
    {
        $this->feedbackToken = bin2hex(random_bytes(16));
    }

    public function getFeedbackToken(): string
    {
        return $this->feedbackToken;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function setMission(Mission $mission): void
    {
        $this->mission = $mission;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getStoredName(): string
    {
        return $this->storedName;
    }

    public function setStoredName(string $storedName): void
    {
        $this->storedName = $storedName;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): void
    {
        $this->uploadedBy = $uploadedBy;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->mission->getName() . ' ' . $this->label;
    }
}
