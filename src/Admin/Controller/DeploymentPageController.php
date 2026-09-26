<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\DeploymentPageType;
use MajesticDev\CommandNetS3\Entity\DeploymentPage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The details shared by every operation page in a Deployment. Same permissions as Operation Pages.
 *
 * @extends AbstractCrudController<DeploymentPage>
 */
#[Route('/command-net-s3/deployment-pages', 'command_net_s3_deployment_pages')]
class DeploymentPageController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.operation_page.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.operation_page.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.operation_page.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.operation_page.manage';

    protected function getEntityClass(): string
    {
        return DeploymentPage::class;
    }

    protected function getTableName(): string
    {
        return 'DeploymentPageTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(DeploymentPageType::class, $data);
    }
}
