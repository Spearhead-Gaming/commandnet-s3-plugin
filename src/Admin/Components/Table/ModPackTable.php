<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\ModPack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('ModPackTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.modpack.view')]
class ModPackTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return ModPack::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('deployment', [
                'field' => 'deployment',
                'searchable' => false,
                'renderer' => fn (?Deployment $deployment) => $deployment === null ? '' : htmlspecialchars($deployment->getName()),
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        $actions = $this->renderAction('forumify_admin_command_net_s3_mod_pack', ['id' => $id], 'eye');

        if (!$this->security->isGranted('command-net-s3.admin.modpack.manage')) {
            return $actions;
        }

        return $actions
            . $this->renderAction('forumify_admin_command_net_s3_mod_packs_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_mod_packs_delete', ['identifier' => $id], 'x');
    }
}
