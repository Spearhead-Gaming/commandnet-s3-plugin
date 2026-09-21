<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\ServerModType;
use MajesticDev\CommandNetS3\Entity\ServerMod;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<ServerMod>
 */
#[Route('/command-net-s3/server-mods', 'command_net_s3_server_mods')]
class ServerModController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.server.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.server.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.server.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.server.manage';

    protected function getEntityClass(): string
    {
        return ServerMod::class;
    }

    protected function getTableName(): string
    {
        return 'ServerModTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(ServerModType::class, $data);
    }
}
