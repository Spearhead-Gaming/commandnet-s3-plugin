<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Repository\MissionNoteRepository;

/**
 * One timestamped line a GM jotted down during a live operation (key events, casualties,
 * objective progress). createdAt is the time it was written. They are the raw record, so they
 * are not editable or deletable from the UI, and they feed the AAR draft afterwards.
 */
#[ORM\Entity(MissionNoteRepository::class)]
#[ORM\Table(name: 's3_mission_note')]
class MissionNote
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Operation $operation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    public function __construct(Operation $operation, ?User $author, string $text)
    {
        $this->operation = $operation;
        $this->author = $author;
        $this->text = $text;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
