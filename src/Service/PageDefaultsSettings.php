<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Forumify\Core\Repository\SettingRepository;
use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Repository\GameServerRepository;

/**
 * The S3-wide defaults for the details operation pages share: used by every operation page,
 * in every Deployment, that neither it nor its Deployment fills in. The last tier of
 * OperationPageDetails.
 */
class PageDefaultsSettings
{
    private const string KEY = 'command_net_s3.page_defaults';

    /** The plain-text settings; the server is stored as an id and loaded separately. */
    private const array TEXT_FIELDS = ['steamCollectionUrl', 'comms', 'roe', 'checklist', 'quickLinks'];

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly GameServerRepository $servers,
    ) {
    }

    /**
     * @return array{server: ?GameServer, steamCollectionUrl: ?string, comms: ?string, roe: ?string, checklist: ?string, quickLinks: ?string}
     */
    public function all(): array
    {
        $stored = (array)$this->settings->get(self::KEY);
        $serverId = $stored['server'] ?? null;

        $values = ['server' => is_numeric($serverId) ? $this->servers->find((int)$serverId) : null];
        foreach (self::TEXT_FIELDS as $field) {
            $value = $stored[$field] ?? null;
            $values[$field] = is_string($value) && trim($value) !== '' ? $value : null;
        }

        /** @var array{server: ?GameServer, steamCollectionUrl: ?string, comms: ?string, roe: ?string, checklist: ?string, quickLinks: ?string} */
        return $values;
    }

    /**
     * @param array<string, mixed> $data the form's data, with the server as an entity
     */
    public function save(array $data): void
    {
        $server = $data['server'] ?? null;
        $stored = ['server' => $server instanceof GameServer ? $server->getId() : null];
        foreach (self::TEXT_FIELDS as $field) {
            $value = $data[$field] ?? null;
            $stored[$field] = is_string($value) && trim($value) !== '' ? $value : null;
        }

        $this->settings->set(self::KEY, $stored);
    }
}
