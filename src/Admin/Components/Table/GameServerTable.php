<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\GameServer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('GameServerTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.server.view')]
class GameServerTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return GameServer::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('host', [
                'field' => 'host',
            ])
            ->addColumn('queryPort', [
                'label' => 'Query port',
                'field' => 'queryPort',
                'searchable' => false,
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.server.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_servers_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_servers_delete', ['identifier' => $id], 'x');
    }
}
