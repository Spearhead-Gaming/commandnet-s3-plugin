<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use MajesticDev\CommandNetS3\Repository\SopVersionRepository;

/**
 * One published revision of a Sop, with a changelog explaining what changed.
 */
#[ORM\Entity(SopVersionRepository::class)]
#[ORM\Table(name: 's3_sop_version')]
class SopVersion
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Sop::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Sop $sop;

    /**
     * Free-form so staff can use whatever scheme they like, e.g. "1.2" or "2026-09 rev".
     */
    #[ORM\Column(length: 50)]
    private string $label = '';

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changelog = null;

    public function getSop(): Sop
    {
        return $this->sop;
    }

    public function setSop(Sop $sop): void
    {
        $this->sop = $sop;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getChangelog(): ?string
    {
        return $this->changelog;
    }

    public function setChangelog(?string $changelog): void
    {
        $this->changelog = $changelog;
    }
}
