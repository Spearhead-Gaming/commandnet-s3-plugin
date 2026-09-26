<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Repository\ModPackRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * The client modpack for one Deployment. Operations in the deployment use it unless a mission
 * built for the operation brings its own mod list. Each change is a new ModPackVersion, so the
 * pack's current list is its newest version and every older one stays downloadable.
 */
#[ORM\Entity(ModPackRepository::class)]
#[ORM\Table(name: 's3_mod_pack')]
#[UniqueEntity(fields: ['deployment'], message: 'That deployment already has a modpack.')]
class ModPack implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\OneToOne(targetEntity: Deployment::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Deployment $deployment = null;

    /**
     * Newest first.
     *
     * @var Collection<int, ModPackVersion>
     */
    #[ORM\OneToMany(targetEntity: ModPackVersion::class, mappedBy: 'modPack')]
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

    public function getDeployment(): ?Deployment
    {
        return $this->deployment;
    }

    public function setDeployment(?Deployment $deployment): void
    {
        $this->deployment = $deployment;
    }

    /**
     * @return Collection<int, ModPackVersion>
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
}
