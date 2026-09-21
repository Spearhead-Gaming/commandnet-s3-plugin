<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\Mission;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('MissionTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.mission.view')]
class MissionTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Mission::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('owner', [
                'field' => 'owner',
                'searchable' => false,
                'renderer' => fn (?User $owner) => $owner === null ? '' : htmlspecialchars($owner->getDisplayName()),
            ])
            ->addColumn('operation', [
                'field' => 'operation',
                'searchable' => false,
                'renderer' => fn (?Operation $operation) => $operation === null ? '' : htmlspecialchars($operation->getTitle()),
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        $actions = $this->renderAction('forumify_admin_command_net_s3_mission', ['id' => $id], 'eye');

        if (!$this->security->isGranted('command-net-s3.admin.mission.manage_all')) {
            return $actions;
        }

        return $actions
            . $this->renderAction('forumify_admin_command_net_s3_missions_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_missions_delete', ['identifier' => $id], 'x');
    }
}
