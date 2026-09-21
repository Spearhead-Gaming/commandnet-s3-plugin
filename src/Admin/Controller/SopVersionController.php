<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\SopVersionType;
use MajesticDev\CommandNetS3\Entity\SopVersion;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<SopVersion>
 */
#[Route('/command-net-s3/sop-versions', 'command_net_s3_sop_versions')]
class SopVersionController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.sop.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.sop.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.sop.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.sop.manage';

    protected function getEntityClass(): string
    {
        return SopVersion::class;
    }

    protected function getTableName(): string
    {
        return 'SopVersionTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(SopVersionType::class, $data);
    }
}
