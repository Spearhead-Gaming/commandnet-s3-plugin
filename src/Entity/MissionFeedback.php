<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Enum\FeedbackKind;
use MajesticDev\CommandNetS3\Repository\MissionFeedbackRepository;

/**
 * A playtester's bug report, balance issue or general feedback on one version of a mission.
 * Resolving it is recorded (and audited) rather than deleting it.
 */
#[ORM\Entity(MissionFeedbackRepository::class)]
#[ORM\Table(name: 's3_mission_feedback')]
class MissionFeedback implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: MissionVersion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private MissionVersion $version;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(length: 20, enumType: FeedbackKind::class)]
    private FeedbackKind $kind = FeedbackKind::BUG;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'boolean')]
    private bool $resolved = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $resolvedBy = null;

    public function __construct(MissionVersion $version, ?User $author)
    {
        $this->version = $version;
        $this->author = $author;
    }

    public function getVersion(): MissionVersion
    {
        return $this->version;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getKind(): FeedbackKind
    {
        return $this->kind;
    }

    public function setKind(FeedbackKind $kind): void
    {
        $this->kind = $kind;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function getResolvedBy(): ?User
    {
        return $this->resolvedBy;
    }

    public function resolve(User $by): void
    {
        $this->resolved = true;
        $this->resolvedBy = $by;
    }

    public function reopen(): void
    {
        $this->resolved = false;
        $this->resolvedBy = null;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->version->getNameForAudit() . ': ' . $this->kind->label();
    }
}
