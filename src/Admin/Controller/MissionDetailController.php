<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Mission;
use MajesticDev\CommandNetS3\Entity\MissionFeedback;
use MajesticDev\CommandNetS3\Entity\MissionVersion;
use MajesticDev\CommandNetS3\Repository\MissionFeedbackRepository;
use MajesticDev\CommandNetS3\Repository\MissionVersionRepository;
use MajesticDev\CommandNetS3\Service\MissionStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A mission's page: its versions (each downloadable) and the playtest feedback on them. Anyone
 * with mission.view can look and download; adding versions and resolving feedback needs
 * mission.manage_all, or mission.manage on a mission you own.
 */
class MissionDetailController extends AbstractController
{
    public function __construct(
        private readonly MissionVersionRepository $versionRepository,
        private readonly MissionFeedbackRepository $feedbackRepository,
        private readonly MissionStorage $storage,
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
        ]);
    }

    #[Route('/command-net-s3/missions/{id}/upload', 'command_net_s3_mission_upload', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function upload(Mission $mission, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.mission.view');
        if (!$this->canManage($mission)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createFormBuilder()
            ->add('label', TextType::class, [
                'label' => 'Version',
                'help' => 'For example v1.3.',
                'constraints' => [new NotBlank(), new Length(max: 50)],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'help' => 'What changed in this build.',
                'attr' => ['rows' => 4],
            ])
            ->add('file', FileType::class, [
                'label' => 'Mission file',
                'help' => 'One of: ' . implode(', ', MissionStorage::EXTENSIONS) . '. Very large files are limited by the server\'s PHP upload size.',
                'constraints' => [
                    new NotBlank(),
                    new File(maxSize: '256M', extensions: MissionStorage::EXTENSIONS, extensionsMessage: 'Upload a .pbo, .vt or .zip file.'),
                ],
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{label: string, notes: ?string, file: UploadedFile} $data */
            $data = $form->getData();
            $file = $data['file'];
            $originalName = $file->getClientOriginalName();
            $size = (int)$file->getSize();

            $storedName = $this->storage->store($file);

            $version = new MissionVersion();
            $version->setMission($mission);
            $version->setLabel($data['label']);
            $version->setNotes($data['notes']);
            $version->setOriginalName($originalName);
            $version->setStoredName($storedName);
            $version->setSize($size);
            $user = $this->getUser();
            $version->setUploadedBy($user instanceof User ? $user : null);

            try {
                $this->versionRepository->save($version);
            } catch (\Throwable $exception) {
                $this->storage->remove($storedName); // don't leave a file nothing points to
                throw $exception;
            }

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
