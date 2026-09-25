<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use MajesticDev\CommandNetS3\Admin\Form\ModPackType;
use MajesticDev\CommandNetS3\Entity\ModPack;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The modpack records (one per deployment). Their versions are published from the pack's own
 * page, see ModPackDetailController.
 *
 * @extends AbstractCrudController<ModPack>
 */
#[Route('/command-net-s3/mod-packs', 'command_net_s3_mod_packs')]
class ModPackController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.modpack.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.modpack.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.modpack.manage';
    protected ?string $permissionDelete = 'command-net-s3.admin.modpack.manage';

    protected function getEntityClass(): string
    {
        return ModPack::class;
    }

    protected function getTableName(): string
    {
        return 'ModPackTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(ModPackType::class, $data);
    }

    /**
     * A new pack has no versions yet, so go straight to where the first one is published.
     */
    protected function redirectAfterSave(mixed $entity, bool $isNew): Response
    {
        if ($isNew && $entity instanceof ModPack) {
            return $this->redirectToRoute('forumify_admin_command_net_s3_mod_pack', ['id' => $entity->getId()]);
        }

        return parent::redirectAfterSave($entity, $isNew);
    }
}
