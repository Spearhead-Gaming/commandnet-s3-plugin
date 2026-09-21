<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\SopType;
use MajesticDev\CommandNetS3\Entity\Sop;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Sop>
 */
#[Route('/command-net-s3/sops', 'command_net_s3_sops')]
class SopController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.sop.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.sop.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.sop.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.sop.manage';

    protected function getEntityClass(): string
    {
        return Sop::class;
    }

    protected function getTableName(): string
    {
        return 'SopTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(SopType::class, $data);
    }
}
