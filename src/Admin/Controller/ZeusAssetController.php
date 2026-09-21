<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\ZeusAssetType;
use MajesticDev\CommandNetS3\Entity\ZeusAsset;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<ZeusAsset>
 */
#[Route('/command-net-s3/zeus-assets', 'command_net_s3_zeus_assets')]
class ZeusAssetController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.zeus_asset.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.zeus_asset.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.zeus_asset.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.zeus_asset.manage';

    protected function getEntityClass(): string
    {
        return ZeusAsset::class;
    }

    protected function getTableName(): string
    {
        return 'ZeusAssetTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(ZeusAssetType::class, $data);
    }
}
