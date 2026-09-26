<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use MajesticDev\CommandNetS3\Repository\DeploymentPageRepository;

/**
 * What an operation page shows for the details it shares: its own value if it has one, otherwise
 * its Deployment's, otherwise the S3-wide default (Page Defaults in the S3 settings). A blank box
 * (or only whitespace) counts as "not filled in", so a page can't hide an inherited detail by
 * leaving it empty; to change one operation, type the different value on that page.
 */
class OperationPageDetails
{
    public function __construct(
        private readonly DeploymentPageRepository $deploymentPages,
        private readonly PageDefaultsSettings $defaults,
    ) {
    }

    /**
     * With no page there is nothing to show, inherited details included: they are extra sections
     * of the page, and an unpublished page's extra sections are for staff only.
     *
     * @return array{server: ?GameServer, steamCollectionUrl: ?string, comms: ?string, roe: ?string, checklist: ?string, quickLinks: ?string}
     */
    public function forPage(?OperationPage $page): array
    {
        if ($page === null) {
            return ['server' => null, 'steamCollectionUrl' => null, 'comms' => null, 'roe' => null, 'checklist' => null, 'quickLinks' => null];
        }

        $operation = $page->getOperation();
        $deployment = method_exists($operation, 'getDeployment') ? $operation->getDeployment() : null;
        $shared = $deployment !== null ? $this->deploymentPages->findOneBy(['deployment' => $deployment]) : null;
        $defaults = $this->defaults->all();

        return [
            'server' => $page->getServer() ?? $shared?->getServer() ?? $defaults['server'],
            'steamCollectionUrl' => self::pick($page->getSteamCollectionUrl(), $shared?->getSteamCollectionUrl(), $defaults['steamCollectionUrl']),
            'comms' => self::pick($page->getComms(), $shared?->getComms(), $defaults['comms']),
            'roe' => self::pick($page->getRoe(), $shared?->getRoe(), $defaults['roe']),
            'checklist' => self::pick($page->getChecklist(), $shared?->getChecklist(), $defaults['checklist']),
            'quickLinks' => self::pick($page->getQuickLinks(), $shared?->getQuickLinks(), $defaults['quickLinks']),
        ];
    }

    /**
     * The first value that isn't blank, in order of precedence.
     */
    public static function pick(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
