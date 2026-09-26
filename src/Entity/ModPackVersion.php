<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Repository\ModPackVersionRepository;

/**
 * One published state of a ModPack: its mods (ModPackMod rows, fixed once published) and a
 * changelog. Never edited afterwards, so a version always downloads as it was published.
 */
#[ORM\Entity(ModPackVersionRepository::class)]
#[ORM\Table(name: 's3_mod_pack_version')]
class ModPackVersion implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: ModPack::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ModPack $modPack;

    /**
     * Free-form so staff can use whatever scheme they like, e.g. "2026.09-r2".
     */
    #[ORM\Column(length: 50)]
    private string $label = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changelog = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function getModPack(): ModPack
    {
        return $this->modPack;
    }

    public function setModPack(ModPack $modPack): void
    {
        $this->modPack = $modPack;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getChangelog(): ?string
    {
        return $this->changelog;
    }

    public function setChangelog(?string $changelog): void
    {
        $this->changelog = $changelog;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->modPack->getName() . ' ' . $this->label;
    }
}
