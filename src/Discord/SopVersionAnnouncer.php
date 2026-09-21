<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Discord;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use MajesticDev\CommandNetS3\Entity\SopVersion;
use MajesticDev\CommandNetS3\Service\DiscordAnnouncementSettings;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Announces a newly published SOP / doctrine version and asks members to acknowledge it.
 */
#[AsEntityListener(Events::postPersist, method: 'postPersist', entity: SopVersion::class)]
class SopVersionAnnouncer
{
    private const int CHANGELOG_EXCERPT = 300;

    public function __construct(
        private readonly DiscordAnnouncer $announcer,
        private readonly DiscordAnnouncementSettings $settings,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function postPersist(SopVersion $version): void
    {
        if (!$this->settings->all()['announceSopVersions']) {
            return;
        }

        $sop = $version->getSop();
        $url = $this->urlGenerator->generate('command_net_s3_sop_show', ['id' => $sop->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = sprintf(
            "**New SOP version:** %s v%s. Please read it and acknowledge.",
            DiscordAnnouncer::plain($sop->getTitle()),
            DiscordAnnouncer::plain($version->getLabel()),
        );
        if ($version->getChangelog() !== null && $version->getChangelog() !== '') {
            $message .= "\n" . DiscordAnnouncer::plain(mb_strimwidth($version->getChangelog(), 0, self::CHANGELOG_EXCERPT, '…'));
        }

        $this->announcer->announce($message . "\n" . $url);
    }
}
