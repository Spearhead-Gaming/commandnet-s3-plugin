<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Entity\ServerMod;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('ServerModTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.server.view')]
class ServerModTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return ServerMod::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('server', [
                'field' => 'server',
                'searchable' => false,
                'renderer' => fn (GameServer $server) => htmlspecialchars($server->getName()),
            ])
            ->addColumn('name', [
                'label' => 'Mod',
                'field' => 'name',
            ])
            ->addColumn('installedVersion', [
                'label' => 'On server',
                'field' => 'installedVersion',
            ])
            ->addColumn('modpackVersion', [
                'label' => 'In modpack',
                'field' => 'modpackVersion',
            ])
            ->addColumn('status', [
                'field' => 'installedVersion',
                'searchable' => false,
                'sortable' => false,
                'renderer' => fn ($value, ServerMod $mod) => sprintf(
                    '<span class="tag%s">%s</span>',
                    $mod->isOutdated() ? ' tag-error' : '',
                    $mod->statusLabel(),
                ),
            ])
            ->addColumn('workshopId', [
                'label' => 'Workshop',
                'field' => 'workshopId',
                'searchable' => false,
                'renderer' => fn (?string $id) => $id === null || $id === ''
                    ? ''
                    : '<a href="https://steamcommunity.com/sharedfiles/filedetails/?id=' . rawurlencode($id) . '" target="_blank" rel="noopener noreferrer">Open</a>',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.server.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_server_mods_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_server_mods_delete', ['identifier' => $id], 'x');
    }
}
