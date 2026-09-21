<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNetS3\Repository\GameServerRepository;

/**
 * A game server the community runs, identified by the host and Steam query port the status
 * dashboard asks about it.
 */
#[ORM\Entity(GameServerRepository::class)]
#[ORM\Table(name: 's3_game_server')]
class GameServer implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $host = '';

    #[ORM\Column(type: 'integer')]
    private int $queryPort = 2303;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getQueryPort(): int
    {
        return $this->queryPort;
    }

    public function setQueryPort(int $queryPort): void
    {
        $this->queryPort = $queryPort;
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
