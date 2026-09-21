<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\Sop;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('SopTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.sop.view')]
class SopTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Sop::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('title', [
                'field' => 'title',
            ])
            ->addColumn('description', [
                'field' => 'description',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        $actions = $this->renderAction('forumify_admin_command_net_s3_sop_acknowledgements', ['id' => $id], 'users');

        if (!$this->security->isGranted('command-net-s3.admin.sop.manage')) {
            return $actions;
        }

        return $actions
            . $this->renderAction('forumify_admin_command_net_s3_sops_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_sops_delete', ['identifier' => $id], 'x');
    }
}
