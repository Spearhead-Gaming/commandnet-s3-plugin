<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Mission;
use MajesticDev\CommandNetS3\Entity\MissionFeedback;
use MajesticDev\CommandNetS3\Entity\MissionVersion;
use MajesticDev\CommandNetS3\Repository\MissionFeedbackRepository;
use MajesticDev\CommandNetS3\Admin\Form\MissionModsType;
use MajesticDev\CommandNetS3\Admin\Form\MissionVersionType;
use MajesticDev\CommandNetS3\Service\MissionModList;
use MajesticDev\CommandNetS3\Service\MissionStorage;
use MajesticDev\CommandNetS3\Service\MissionVersionUploader;
use MajesticDev\CommandNetS3\Service\ModListParser;
use MajesticDev\CommandNetS3\Service\PresetRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A mission's page: its versions (each downloadable) and the playtest feedback on them. Anyone
 * with mission.view can look and download; adding versions and resolving feedback needs
 * mission.manage_all, or mission.manage on a mission you own.
 */
class MissionDetailController extends AbstractController
{
    public function __construct(
        private readonly MissionFeedbackRepository $feedbackRepository,
        private readonly MissionStorage $storage,
        private readonly MissionVersionUploader $uploader,
        private readonly MissionModList $modList,
        private readonly PresetRenderer $presetRenderer,
    ) {
    }

    #[Route('/command-net-s3/missions/{id}', 'command_net_s3_mission', requirements: ['id' => '\d+'])]
    public function detail(Mission $mission): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');

        $feedback = [];
        foreach ($mission->getVersions() as $version) {
            $feedback[$version->getId()] = $this->feedbackRepository->findBy(['version' => $version], ['id' => 'DESC']);
        }

        return $this->render('@CommandNetS3Plugin/admin/mission/detail.html.twig', [
            'mission' => $mission,
            'feedback' => $feedback,
            'canManage' => $this->canManage($mission),
            'mods' => array_map(static fn ($mod) => [
                'name' => $mod->name,
                'dlc' => $mod->kind->value === 'dlc',
                'url' => ModListParser::url($mod->kind, $mod->steamId),
            ], $this->modList->forMission($mission)),
        ]);
    }

    #[Route('/command-net-s3/missions/{id}/mods', 'command_net_s3_mission_mods', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function mods(Mission $mission, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');
        if (!$this->canManage($mission)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(MissionModsType::class, null, ['mod_text' => $this->modList->toText($mission)]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{mods: list<\MajesticDev\CommandNetS3\Service\ParsedMod>} $data */
            $data = $form->getData();
            $this->modList->replace($mission, $data['mods']);

            $this->addFlash('success', 'Mod list saved.');
            return $this->redirectToRoute('forumify_admin_command_net_s3_mission', ['id' => $mission->getId()]);
        }

        return $this->render('@CommandNetS3Plugin/admin/mission/mods.html.twig', [
            'mission' => $mission,
            'form' => $form->createView(),
            'count' => count($this->modList->forMission($mission)),
        ]);
    }

    #[Route('/command-net-s3/missions/{id}/modpack.html', 'command_net_s3_mission_modpack', requirements: ['id' => '\d+'])]
    public function modpack(Mission $mission): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');

        $mods = $this->modList->forMission($mission);
        if ($mods === []) {
            throw $this->createNotFoundException('This mission has no mod list yet.');
        }

        return $this->presetRenderer->download($mission->getName(), $mods);
    }

    #[Route('/command-net-s3/missions/{id}/upload', 'command_net_s3_mission_upload', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function upload(Mission $mission, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');
        if (!$this->canManage($mission)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(MissionVersionType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{label: string, notes: ?string, file: UploadedFile} $data */
            $data = $form->getData();
            $user = $this->getUser();
            $this->uploader->upload($mission, $data['label'], $data['notes'], $data['file'], $user instanceof User ? $user : null);

            $this->addFlash('success', 'Version uploaded.');
            return $this->redirectToRoute('forumify_admin_command_net_s3_mission', ['id' => $mission->getId()]);
        }

        return $this->render('@CommandNetS3Plugin/admin/mission/upload.html.twig', [
            'mission' => $mission,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/command-net-s3/missions/versions/{id}/download', 'command_net_s3_mission_download', requirements: ['id' => '\d+'])]
    public function download(MissionVersion $version): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');

        $path = $this->storage->path($version->getStoredName());
        if (!is_file($path)) {
            throw $this->createNotFoundException('The mission file is missing from storage.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $version->getOriginalName());

        return $response;
    }

    #[Route('/command-net-s3/missions/feedback/{id}/resolve', 'command_net_s3_mission_feedback_resolve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function resolve(MissionFeedback $feedback, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');

        $mission = $feedback->getVersion()->getMission();
        if (!$this->canManage($mission)) {
            throw $this->createAccessDeniedException();
        }

        $back = $this->redirectToRoute('forumify_admin_command_net_s3_mission', ['id' => $mission->getId()]);
        if (!$this->isCsrfTokenValid('mission_feedback_resolve_' . $feedback->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Your session expired, please try again.');
            return $back;
        }

        $user = $this->getUser();
        if ($feedback->isResolved() || !$user instanceof User) {
            $feedback->reopen();
        } else {
            $feedback->resolve($user);
        }
        $this->feedbackRepository->save($feedback);

        return $back;
    }

    private function canManage(Mission $mission): bool
    {
        if ($this->isGranted('command-net-s3.admin.mission.manage_all')) {
            return true;
        }

        $owner = $mission->getOwner();
        $user = $this->getUser();

        return $this->isGranted('command-net-s3.admin.mission.manage')
            && $owner !== null
            && $user instanceof User
            && $owner->getId() === $user->getId();
    }
}
