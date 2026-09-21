<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Entity\SopVersion;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('SopVersionTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.sop.view')]
class SopVersionTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return SopVersion::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('sop', [
                'label' => 'Document',
                'field' => 'sop',
                'searchable' => false,
                'renderer' => fn (Sop $sop) => $sop->getTitle(),
            ])
            ->addColumn('label', [
                'label' => 'Version',
                'field' => 'label',
            ])
            ->addColumn('createdAt', [
                'label' => 'Published',
                'field' => 'createdAt',
                'searchable' => false,
                'renderer' => fn (\DateTimeInterface $createdAt) => $createdAt->format('Y-m-d H:i'),
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.sop.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_sop_versions_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_sop_versions_delete', ['identifier' => $id], 'x');
    }
}
