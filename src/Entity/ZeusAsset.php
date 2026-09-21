<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Entity\Enum\AssetCategory;
use MajesticDev\CommandNetS3\Repository\ZeusAssetRepository;

/**
 * One entry in the Zeus/GM catalog of terrain, faction and other asset packs the community
 * uses, with a link to the mod and short notes for whoever is planning a mission.
 */
#[ORM\Entity(ZeusAssetRepository::class)]
#[ORM\Table(name: 's3_zeus_asset')]
class ZeusAsset implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 20, enumType: AssetCategory::class)]
    private AssetCategory $category = AssetCategory::MISC;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $modUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): AssetCategory
    {
        return $this->category;
    }

    public function setCategory(AssetCategory $category): void
    {
        $this->category = $category;
    }

    public function getModUrl(): ?string
    {
        return $this->modUrl;
    }

    public function setModUrl(?string $modUrl): void
    {
        $this->modUrl = $modUrl;
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
        return $this->name;
    }
}
