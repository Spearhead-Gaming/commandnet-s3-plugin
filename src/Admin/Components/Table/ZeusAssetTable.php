<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use MajesticDev\CommandNetS3\Entity\Enum\AssetCategory;
use MajesticDev\CommandNetS3\Entity\ZeusAsset;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('ZeusAssetTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.zeus_asset.view')]
class ZeusAssetTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return ZeusAsset::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            // Searchable on purpose: typing "terrain" or "faction" in the search box filters by tag.
            ->addColumn('category', [
                'field' => 'category',
                'renderer' => fn (AssetCategory $category) => $category->label(),
            ])
            ->addColumn('modUrl', [
                'label' => 'Mod',
                'field' => 'modUrl',
                'searchable' => false,
                'renderer' => $this->renderModLink(...),
            ])
            ->addColumn('notes', [
                'field' => 'notes',
            ])
            ->addActionColumn($this->renderActionColumn(...));
    }

    private function renderModLink(?string $url): string
    {
        // Only ever link out to web URLs, whatever ended up in the database.
        if ($url === null || !preg_match('#^https?://#i', $url)) {
            return '';
        }

        return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Open</a>';
    }

    protected function renderActionColumn(int $id): string
    {
        if (!$this->security->isGranted('command-net-s3.admin.zeus_asset.manage')) {
            return '';
        }

        return $this->renderAction('forumify_admin_command_net_s3_zeus_assets_edit', ['identifier' => $id], 'pencil-simple-line')
            . $this->renderAction('forumify_admin_command_net_s3_zeus_assets_delete', ['identifier' => $id], 'x');
    }
}
