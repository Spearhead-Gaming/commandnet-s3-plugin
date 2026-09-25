<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Admin\Form\MissionType;
use MajesticDev\CommandNetS3\Entity\Mission;
use MajesticDev\CommandNetS3\Service\MissionModList;
use MajesticDev\CommandNetS3\Service\MissionVersionUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

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

    private MissionModList $modList;
    private MissionVersionUploader $uploader;

    #[Required]
    public function setModList(MissionModList $modList, MissionVersionUploader $uploader): void
    {
        $this->modList = $modList;
        $this->uploader = $uploader;
    }

    protected function getForm(?object $data): FormInterface
    {
        // Start the mod list box from what is stored, so editing the text edits the list.
        $modText = $data instanceof Mission && $data->getId() !== null ? $this->modList->toText($data) : '';

        return $this->createForm(MissionType::class, $data, [
            'mod_text' => $modText,
            'with_version' => !$data instanceof Mission || $data->getId() === null,
        ]);
    }

    protected function save(bool $isNew, FormInterface $form): object
    {
        $mission = $form->getData();
        $user = $this->getUser();
        if ($isNew && $mission instanceof Mission && $mission->getOwner() === null && $user instanceof User) {
            $mission->setOwner($user);
        }

        $saved = parent::save($isNew, $form);

        // The list becomes what was uploaded or typed. The mission has to exist first.
        if ($saved instanceof Mission) {
            /** @var array{mods: list<\MajesticDev\CommandNetS3\Service\ParsedMod>} $mods */
            $mods = $form->get('mods')->getData();
            $this->modList->replace($saved, $mods['mods']);
        }

        if ($isNew && $saved instanceof Mission && $form->has('version')) {
            /** @var array{label: ?string, notes: ?string, file: ?UploadedFile} $version */
            $version = $form->get('version')->getData();
            if ($version['file'] instanceof UploadedFile) {
                $this->uploader->upload($saved, (string)$version['label'], $version['notes'], $version['file'], $user instanceof User ? $user : null);
            }
        }

        return $saved;
    }

    /**
     * A new mission lands on its own page, where versions, the mod list and feedback live.
     */
    protected function redirectAfterSave(mixed $entity, bool $isNew): Response
    {
        if ($isNew && $entity instanceof Mission) {
            return $this->redirectToRoute('forumify_admin_command_net_s3_mission', ['id' => $entity->getId()]);
        }

        return parent::redirectAfterSave($entity, $isNew);
    }
}
