<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\BriefingType;
use MajesticDev\CommandNetS3\Entity\Briefing;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Briefing>
 */
#[Route('/command-net-s3/briefings', 'command_net_s3_briefings')]
class BriefingController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.briefing.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.briefing.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.briefing.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.briefing.manage';

    protected function getEntityClass(): string
    {
        return Briefing::class;
    }

    protected function getTableName(): string
    {
        return 'BriefingTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(BriefingType::class, $data);
    }
}
