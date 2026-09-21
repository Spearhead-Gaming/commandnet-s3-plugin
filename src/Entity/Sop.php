<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Repository\SopRepository;

/**
 * A standing document (SOP, doctrine, loadout rules). The text lives in SopVersion; the
 * newest version is the current one, and members acknowledge a specific version.
 */
#[ORM\Entity(SopRepository::class)]
#[ORM\Table(name: 's3_sop')]
class Sop
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Newest first.
     *
     * @var Collection<int, SopVersion>
     */
    #[ORM\OneToMany(targetEntity: SopVersion::class, mappedBy: 'sop', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return Collection<int, SopVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getCurrentVersion(): ?SopVersion
    {
        return $this->versions->first() ?: null;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
