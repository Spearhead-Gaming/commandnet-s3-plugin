<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\OperationPageType;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<OperationPage>
 */
#[Route('/command-net-s3/operation-pages', 'command_net_s3_operation_pages')]
class OperationPageController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.operation_page.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.operation_page.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.operation_page.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.operation_page.manage';

    protected function getEntityClass(): string
    {
        return OperationPage::class;
    }

    protected function getTableName(): string
    {
        return 'OperationPageTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(OperationPageType::class, $data);
    }
}
