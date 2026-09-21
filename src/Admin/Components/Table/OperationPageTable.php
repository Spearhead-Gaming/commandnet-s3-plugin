<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('OperationPageTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.operation_page.view')]
class OperationPageTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return OperationPage::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('operation', [
                'field' => 'operation',
                'searchable' => false,
                'renderer' => fn (Operation $operation) => htmlspecialchars($operation->getTitle()),
            ])
            ->addColumn('orderNumber', [
                'label' => 'Order',
                'field' => 'orderNumber',
            ])
            ->addColumn('published', [
                'field' => 'published',
                'searchable' => false,
                'renderer' => fn (bool $published) => $published ? 'Published' : 'Draft',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id, OperationPage $page): string
    {
        $actions = $this->renderAction('command_net_s3_operation_info', ['id' => $page->getOperation()->getId()], 'eye');

        if (!$this->security->isGranted('command-net-s3.admin.operation_page.manage')) {
            return $actions;
        }

        return $actions
            . $this->renderAction('forumify_admin_command_net_s3_operation_pages_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_operation_pages_delete', ['identifier' => $id], 'x');
    }
}
