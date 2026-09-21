<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\GameServerType;
use MajesticDev\CommandNetS3\Entity\GameServer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<GameServer>
 */
#[Route('/command-net-s3/servers', 'command_net_s3_servers')]
class GameServerController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.server.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.server.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.server.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.server.manage';

    protected function getEntityClass(): string
    {
        return GameServer::class;
    }

    protected function getTableName(): string
    {
        return 'GameServerTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(GameServerType::class, $data);
    }
}
