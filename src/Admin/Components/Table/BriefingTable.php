<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\Briefing;
use MajesticDev\CommandNetS3\Entity\Enum\BriefingStatus;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('BriefingTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.briefing.view')]
class BriefingTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Briefing::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('missionName', [
                'label' => 'Mission',
                'field' => 'missionName',
            ])
            ->addColumn('status', [
                'field' => 'status',
                'searchable' => false,
                'renderer' => fn (BriefingStatus $status) => $status->label(),
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.briefing.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_briefings_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_briefings_delete', ['identifier' => $id], 'x');
    }
}
