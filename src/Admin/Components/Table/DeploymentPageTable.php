<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\DeploymentPage;
use MajesticDev\CommandNetS3\Entity\GameServer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('DeploymentPageTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.operation_page.view')]
class DeploymentPageTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return DeploymentPage::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('deployment', [
                'field' => 'deployment',
                'searchable' => false,
                'renderer' => fn (Deployment $deployment) => htmlspecialchars($deployment->getName()),
            ])
            ->addColumn('server', [
                'field' => 'server',
                'searchable' => false,
                'renderer' => fn (?GameServer $server) => $server !== null ? htmlspecialchars($server->getName()) : '-',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.operation_page.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_deployment_pages_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_deployment_pages_delete', ['identifier' => $id], 'x');
    }
}
