<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Repository\SopAcknowledgementRepository;

/**
 * A member's "I have read this" for one specific SopVersion. createdAt is the moment they
 * acknowledged. A new version starts with no acknowledgements, so everyone has to re-read it.
 */
#[ORM\Entity(SopAcknowledgementRepository::class)]
#[ORM\Table(name: 's3_sop_acknowledgement')]
#[ORM\UniqueConstraint(name: 's3_sop_ack_unique', columns: ['sop_version_id', 'user_id'])]
class SopAcknowledgement
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: SopVersion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SopVersion $sopVersion;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function __construct(SopVersion $sopVersion, User $user)
    {
        $this->sopVersion = $sopVersion;
        $this->user = $user;
    }

    public function getSopVersion(): SopVersion
    {
        return $this->sopVersion;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
