<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Admin\Form\MissionType;
use MajesticDev\CommandNetS3\Entity\Mission;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Any Mission Dev with manage can create a mission and becomes its owner. Editing or deleting
 * the mission record itself is for leads (manage_all); an owner works through versions and
 * feedback instead, see MissionDetailController.
 *
 * @extends AbstractCrudController<Mission>
 */
#[Route('/command-net-s3/missions', 'command_net_s3_missions')]
class MissionController extends AbstractCrudController
{
    protected ?string $permissionView = 'command-net-s3.admin.mission.view';
    protected ?string $permissionCreate = 'command-net-s3.admin.mission.manage';
    protected ?string $permissionEdit = 'command-net-s3.admin.mission.manage_all';
    protected ?string $permissionDelete = 'command-net-s3.admin.mission.manage_all';

    protected function getEntityClass(): string
    {
        return Mission::class;
    }

    protected function getTableName(): string
    {
        return 'MissionTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(MissionType::class, $data);
    }

    protected function save(bool $isNew, FormInterface $form): object
    {
        $mission = $form->getData();
        $user = $this->getUser();
        if ($isNew && $mission instanceof Mission && $mission->getOwner() === null && $user instanceof User) {
            $mission->setOwner($user);
        }

        return parent::save($isNew, $form);
    }
}
