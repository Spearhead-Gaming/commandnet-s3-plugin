<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Entity\Enum\ModKind;
use MajesticDev\CommandNetS3\Repository\ModPackModRepository;
use MajesticDev\CommandNetS3\Service\ParsedMod;

/**
 * One mod (or DLC) in a published ModPackVersion. Written once, when the version is published
 * (see ModPackService), and never changed, so these rows are not audited individually.
 */
#[ORM\Entity(ModPackModRepository::class)]
#[ORM\Table(name: 's3_mod_pack_mod')]
class ModPackMod
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: ModPackVersion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ModPackVersion $version;

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

    public function __construct(ModPackVersion $version, ParsedMod $mod, int $position)
    {
        $this->version = $version;
        $this->name = $mod->name;
        $this->kind = $mod->kind;
        $this->steamId = $mod->steamId;
        $this->position = $position;
    }

    public function asParsed(): ParsedMod
    {
        return new ParsedMod($this->name, $this->kind, $this->steamId);
    }
}
