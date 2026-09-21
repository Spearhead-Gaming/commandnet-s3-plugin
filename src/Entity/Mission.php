<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Repository\MissionRepository;

/**
 * A mission a Mission Dev is building, optionally tied to the operation it is for. The mission
 * files themselves are MissionVersions, so the exact build used in a given operation stays
 * retrievable.
 */
#[ORM\Entity(MissionRepository::class)]
#[ORM\Table(name: 's3_mission')]
class Mission implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Operation $operation = null;

    /**
     * Newest first.
     *
     * @var Collection<int, MissionVersion>
     */
    #[ORM\OneToMany(targetEntity: MissionVersion::class, mappedBy: 'mission')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * @return Collection<int, MissionVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
