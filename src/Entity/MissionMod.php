<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Entity\Enum\ModKind;
use MajesticDev\CommandNetS3\Repository\MissionModRepository;
use MajesticDev\CommandNetS3\Service\ModListParser;
use MajesticDev\CommandNetS3\Service\ParsedMod;

/**
 * One mod (or DLC) in a mission's mod list. The list is always replaced as a whole, from a typed
 * list or an uploaded launcher preset (see MissionModList), so these rows are never edited one
 * at a time and are not audited individually.
 */
#[ORM\Entity(MissionModRepository::class)]
#[ORM\Table(name: 's3_mission_mod')]
class MissionMod
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Mission::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Mission $mission;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 10, enumType: ModKind::class)]
    private ModKind $kind;

    /**
     * Steam Workshop id for a mod, store app id for a DLC; null for a local mod.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $steamId;

    #[ORM\Column(type: 'integer')]
    private int $position;

    public function __construct(Mission $mission, ParsedMod $mod, int $position)
    {
        $this->mission = $mission;
        $this->name = $mod->name;
        $this->kind = $mod->kind;
        $this->steamId = $mod->steamId;
        $this->position = $position;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKind(): ModKind
    {
        return $this->kind;
    }

    public function getSteamId(): ?string
    {
        return $this->steamId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getUrl(): ?string
    {
        return ModListParser::url($this->kind, $this->steamId);
    }

    public function asParsed(): ParsedMod
    {
        return new ParsedMod($this->name, $this->kind, $this->steamId);
    }
}
