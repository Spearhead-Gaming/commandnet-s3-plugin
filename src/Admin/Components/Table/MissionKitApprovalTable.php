<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNet\Entity\Equipment;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\MissionKitApproval;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('MissionKitApprovalTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.loadout.view')]
class MissionKitApprovalTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return MissionKitApproval::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('operation', [
                'field' => 'operation',
                'searchable' => false,
                'renderer' => fn (Operation $operation) => htmlspecialchars($operation->getTitle()),
            ])
            ->addColumn('equipment', [
                'field' => 'equipment',
                'searchable' => false,
                'renderer' => fn (Equipment $equipment) => htmlspecialchars($equipment->getName()),
            ])
            ->addColumn('notes', [
                'field' => 'notes',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.loadout.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_kit_approvals_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_kit_approvals_delete', ['identifier' => $id], 'x');
    }
}
