<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Discord;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use MajesticDev\CommandNetS3\Entity\Briefing;
use MajesticDev\CommandNetS3\Entity\Enum\BriefingStatus;
use MajesticDev\CommandNetS3\Service\DiscordAnnouncementSettings;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Announces a briefing the moment it becomes Ready, whether it was created that way or
 * flipped from Draft. Editing an already-Ready briefing does not announce it again.
 */
#[AsEntityListener(Events::postPersist, method: 'postPersist', entity: Briefing::class)]
#[AsEntityListener(Events::preUpdate, method: 'preUpdate', entity: Briefing::class)]
#[AsEntityListener(Events::postUpdate, method: 'postUpdate', entity: Briefing::class)]
class BriefingReadyAnnouncer
{
    /** @var array<int, true> */
    private array $becameReady = [];

    public function __construct(
        private readonly DiscordAnnouncer $announcer,
        private readonly DiscordAnnouncementSettings $settings,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function postPersist(Briefing $briefing): void
    {
        if ($briefing->getStatus() === BriefingStatus::READY) {
            $this->announce($briefing);
        }
    }

    public function preUpdate(Briefing $briefing, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('status') && $args->getNewValue('status') === BriefingStatus::READY) {
            $this->becameReady[spl_object_id($briefing)] = true;
        }
    }

    public function postUpdate(Briefing $briefing): void
    {
        // Wait for the update to actually be written before telling anyone about it.
        if (isset($this->becameReady[spl_object_id($briefing)])) {
            unset($this->becameReady[spl_object_id($briefing)]);
            $this->announce($briefing);
        }
    }

    private function announce(Briefing $briefing): void
    {
        if (!$this->settings->all()['announceBriefings']) {
            return;
        }

        $operation = $briefing->getOperation();
        $url = $this->urlGenerator->generate(
            'command_net_s3_briefing',
            ['id' => $operation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->announcer->announce(sprintf(
            "**Briefing ready:** %s (%s)\n%s",
            DiscordAnnouncer::plain($briefing->getMissionName()),
            DiscordAnnouncer::plain($operation->getTitle()),
            $url,
        ));
    }
}
