<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Repository\DeploymentPageRepository;

/**
 * The details every operation page in a Deployment shares: the server, Steam collection, comms
 * plan, rules of engagement, checklist and quick links. Set once here; an operation page shows
 * these for any of them it leaves blank, and keeps its own value where it has one. What differs
 * per operation (timeline, task organization, mission data, order number, summary) is not here.
 *
 * The text fields hold one entry per line, columns split with "|", exactly like the same fields
 * on OperationPage; see OperationPageParser.
 */
#[ORM\Entity(DeploymentPageRepository::class)]
#[ORM\Table(name: 's3_deployment_page')]
class DeploymentPage implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\OneToOne(targetEntity: Deployment::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Deployment $deployment;

    #[ORM\ManyToOne(targetEntity: GameServer::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?GameServer $server = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $steamCollectionUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comms = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $roe = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $checklist = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $quickLinks = null;

    public function getDeployment(): Deployment
    {
        return $this->deployment;
    }

    public function setDeployment(Deployment $deployment): void
    {
        $this->deployment = $deployment;
    }

    public function getServer(): ?GameServer
    {
        return $this->server;
    }

    public function setServer(?GameServer $server): void
    {
        $this->server = $server;
    }

    public function getSteamCollectionUrl(): ?string
    {
        return $this->steamCollectionUrl;
    }

    public function setSteamCollectionUrl(?string $steamCollectionUrl): void
    {
        $this->steamCollectionUrl = $steamCollectionUrl;
    }

    public function getComms(): ?string
    {
        return $this->comms;
    }

    public function setComms(?string $comms): void
    {
        $this->comms = $comms;
    }

    public function getRoe(): ?string
    {
        return $this->roe;
    }

    public function setRoe(?string $roe): void
    {
        $this->roe = $roe;
    }

    public function getChecklist(): ?string
    {
        return $this->checklist;
    }

    public function setChecklist(?string $checklist): void
    {
        $this->checklist = $checklist;
    }

    public function getQuickLinks(): ?string
    {
        return $this->quickLinks;
    }

    public function setQuickLinks(?string $quickLinks): void
    {
        $this->quickLinks = $quickLinks;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->deployment->getName();
    }
}
