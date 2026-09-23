<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use MajesticDev\CommandNetS3\Repository\GameServerRepository;
use MajesticDev\CommandNetS3\Repository\ServerModRepository;
use MajesticDev\CommandNetS3\Service\ServerQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * One page showing whether each server is up, who is on it and what it is running, plus how
 * many of its mods are out of date. Read-only: nothing here changes a server.
 */
class ServerStatusController extends AbstractController
{
    public function __construct(
        private readonly GameServerRepository $serverRepository,
        private readonly ServerModRepository $modRepository,
        private readonly ServerQuery $query,
    ) {
    }

    #[Route('/command-net-s3/server-status', 'command_net_s3_server_status')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.server.view');

        $rows = [];
        foreach ($this->serverRepository->findBy([], ['name' => 'ASC']) as $server) {
            $mods = $this->modRepository->findBy(['server' => $server]);
            $info = $this->query->query($server);
            $rows[] = [
                'server' => $server,
                'info' => $info,
                'error' => $info === null ? $this->query->getLastError() : null,
                'modCount' => count($mods),
                'outdated' => count(array_filter($mods, static fn ($mod) => $mod->isOutdated())),
            ];
        }

        return $this->render('@CommandNetS3Plugin/admin/server/status.html.twig', ['rows' => $rows]);
    }
}
