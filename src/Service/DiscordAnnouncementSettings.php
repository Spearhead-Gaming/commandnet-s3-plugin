<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Forumify\Core\Repository\SettingRepository;

/**
 * Which S3 events get announced on Discord. Everything is off until a Server Admin turns
 * it on, since enabling it starts posting to the community's Discord servers.
 */
class DiscordAnnouncementSettings
{
    public const array DEFAULTS = [
        'announceBriefings' => false,
        'announceSopVersions' => false,
        'announceModPackVersions' => false,
    ];

    public function __construct(private readonly SettingRepository $settings)
    {
    }

    /**
     * @return array{announceBriefings: bool, announceSopVersions: bool, announceModPackVersions: bool}
     */
    public function all(): array
    {
        /** @var array{announceBriefings: bool, announceSopVersions: bool, announceModPackVersions: bool} */
        return array_replace(self::DEFAULTS, (array)$this->settings->get('command_net_s3.discord'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): void
    {
        $this->settings->set('command_net_s3.discord', array_intersect_key($data, self::DEFAULTS));
    }
}
