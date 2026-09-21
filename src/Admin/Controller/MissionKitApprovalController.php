<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\MissionKitApprovalType;
use MajesticDev\CommandNetS3\Entity\MissionKitApproval;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<MissionKitApproval>
 */
#[Route('/command-net-s3/kit-approvals', 'command_net_s3_kit_approvals')]
class MissionKitApprovalController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.loadout.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.loadout.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.loadout.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.loadout.manage';

    protected function getEntityClass(): string
    {
        return MissionKitApproval::class;
    }

    protected function getTableName(): string
    {
        return 'MissionKitApprovalTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(MissionKitApprovalType::class, $data);
    }
}
