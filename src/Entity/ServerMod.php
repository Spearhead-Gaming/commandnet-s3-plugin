<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Repository\ServerModRepository;

/**
 * A mod a server runs, with the version installed on the server and the version in the
 * client-side modpack. Update history is the audit log: every change to installedVersion is
 * recorded there with who made it and when.
 */
#[ORM\Entity(ServerModRepository::class)]
#[ORM\Table(name: 's3_server_mod')]
class ServerMod implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: GameServer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameServer $server;

    #[ORM\Column(length: 150)]
    private string $name = '';

    /**
     * Steam Workshop id, for linking to the mod.
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $workshopId = null;

    #[ORM\Column(length: 50)]
    private string $installedVersion = '';

    /**
     * What the client-side modpack currently ships. Unset means nobody has said, so no flag.
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $modpackVersion = null;

    public function getServer(): GameServer
    {
        return $this->server;
    }

    public function setServer(GameServer $server): void
    {
        $this->server = $server;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getWorkshopId(): ?string
    {
        return $this->workshopId;
    }

    public function setWorkshopId(?string $workshopId): void
    {
        $this->workshopId = $workshopId;
    }

    public function getInstalledVersion(): string
    {
        return $this->installedVersion;
    }

    public function setInstalledVersion(string $installedVersion): void
    {
        $this->installedVersion = $installedVersion;
    }

    public function getModpackVersion(): ?string
    {
        return $this->modpackVersion;
    }

    public function setModpackVersion(?string $modpackVersion): void
    {
        $this->modpackVersion = $modpackVersion;
    }

    public function isOutdated(): bool
    {
        return $this->compareToModpack() < 0;
    }

    public function statusLabel(): string
    {
        return match ($this->compareToModpack()) {
            -1 => 'Out of date',
            1 => 'Ahead of modpack',
            default => 'Up to date',
        };
    }

    /**
     * -1 when the server is behind the modpack, 1 when ahead, 0 when equal or the modpack
     * version isn't known.
     */
    private function compareToModpack(): int
    {
        if ($this->modpackVersion === null || trim($this->modpackVersion) === '') {
            return 0;
        }

        return version_compare(trim($this->installedVersion), trim($this->modpackVersion)) <=> 0;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->server->getName() . ': ' . $this->name;
    }
}
