<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Discord;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use MajesticDev\CommandNetS3\Entity\ModPackVersion;
use MajesticDev\CommandNetS3\Service\DiscordAnnouncementSettings;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Announces a newly published modpack version with what changed, so players update their mods
 * before the next operation.
 */
#[AsEntityListener(Events::postPersist, method: 'postPersist', entity: ModPackVersion::class)]
class ModPackVersionAnnouncer
{
    private const int CHANGELOG_EXCERPT = 600;

    public function __construct(
        private readonly DiscordAnnouncer $announcer,
        private readonly DiscordAnnouncementSettings $settings,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function postPersist(ModPackVersion $version): void
    {
        if (!$this->settings->all()['announceModPackVersions']) {
            return;
        }

        $message = sprintf(
            "**Modpack updated:** %s %s. Update your mods before the next operation.",
            DiscordAnnouncer::plain($version->getModPack()->getName()),
            DiscordAnnouncer::plain($version->getLabel()),
        );
        if ($version->getChangelog() !== null && $version->getChangelog() !== '') {
            $message .= "\n" . DiscordAnnouncer::plain(mb_strimwidth($version->getChangelog(), 0, self::CHANGELOG_EXCERPT, '…'));
        }

        $this->announcer->announce($message . "\n" . $this->urlGenerator->generate('command_net_s3_operation_current', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
