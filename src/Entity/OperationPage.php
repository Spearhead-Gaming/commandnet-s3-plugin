<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Repository\OperationPageRepository;

/**
 * The extra content for an operation's public information page: everything the operation
 * itself doesn't already hold (order number, timeline, task organization, comms, ROE, checklist,
 * quick links, which server it runs on). The rest of the page is read live from the operation,
 * its briefing, its RSVPs and the server, so it never goes stale.
 *
 * The text fields hold one entry per line, columns split with "|"; see OperationPageParser.
 */
#[ORM\Entity(OperationPageRepository::class)]
#[ORM\Table(name: 's3_operation_page')]
class OperationPage implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\OneToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Operation $operation;

    /**
     * Until this is on, only staff see this content; everyone else still gets the operation's
     * own details.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $published = false;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $summary = null;

    #[ORM\ManyToOne(targetEntity: GameServer::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?GameServer $server = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $presetUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $steamCollectionUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $timeline = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $taskOrg = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $missionData = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comms = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $roe = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $checklist = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $quickLinks = null;

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getServer(): ?GameServer
    {
        return $this->server;
    }

    public function setServer(?GameServer $server): void
    {
        $this->server = $server;
    }

    public function getPresetUrl(): ?string
    {
        return $this->presetUrl;
    }

    public function setPresetUrl(?string $presetUrl): void
    {
        $this->presetUrl = $presetUrl;
    }

    public function getSteamCollectionUrl(): ?string
    {
        return $this->steamCollectionUrl;
    }

    public function setSteamCollectionUrl(?string $steamCollectionUrl): void
    {
        $this->steamCollectionUrl = $steamCollectionUrl;
    }

    public function getTimeline(): ?string
    {
        return $this->timeline;
    }

    public function setTimeline(?string $timeline): void
    {
        $this->timeline = $timeline;
    }

    public function getTaskOrg(): ?string
    {
        return $this->taskOrg;
    }

    public function setTaskOrg(?string $taskOrg): void
    {
        $this->taskOrg = $taskOrg;
    }

    public function getMissionData(): ?string
    {
        return $this->missionData;
    }

    public function setMissionData(?string $missionData): void
    {
        $this->missionData = $missionData;
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
        return $this->operation->getTitle();
    }
}
