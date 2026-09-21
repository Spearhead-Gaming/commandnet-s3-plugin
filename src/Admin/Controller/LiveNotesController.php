<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNet\Repository\OperationRepository;
use MajesticDev\CommandNetS3\Entity\MissionNote;
use MajesticDev\CommandNetS3\Repository\MissionNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A GM's quick timestamped notes during a live operation, which become the starting text of the
 * after-action report afterwards. Notes can only be added, so the record stays honest.
 */
class LiveNotesController extends AbstractController
{
    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly MissionNoteRepository $noteRepository,
    ) {
    }

    #[Route('/command-net-s3/live-notes', 'command_net_s3_live_notes')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.live_notes.view');

        return $this->render('@CommandNetS3Plugin/admin/live_notes/index.html.twig', [
            'operations' => $this->operationRepository->findBy([], ['startDateTime' => 'DESC'], 30),
        ]);
    }

    #[Route('/command-net-s3/live-notes/{id}', 'command_net_s3_live_notes_show', requirements: ['id' => '\d+'])]
    public function show(Operation $operation, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.live_notes.view');
        $canWrite = $this->isGranted('command-net-s3.admin.live_notes.manage');

        $form = $this->createFormBuilder()
            ->add('text', TextareaType::class, [
                'label' => 'Note',
                'attr' => ['rows' => 3, 'autofocus' => true],
                'constraints' => [new NotBlank(), new Length(max: 2000)],
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$canWrite) {
                throw $this->createAccessDeniedException();
            }

            /** @var array{text: string} $data */
            $data = $form->getData();
            $user = $this->getUser();
            $this->noteRepository->save(new MissionNote($operation, $user instanceof User ? $user : null, trim($data['text'])));

            return $this->redirectToRoute('forumify_admin_command_net_s3_live_notes_show', ['id' => $operation->getId()]);
        }

        $notes = $this->noteRepository->findBy(['operation' => $operation], ['id' => 'ASC']);

        return $this->render('@CommandNetS3Plugin/admin/live_notes/show.html.twig', [
            'operation' => $operation,
            'notes' => $notes,
            'canWrite' => $canWrite,
            'form' => $form->createView(),
            'draft' => implode("\n", array_map(
                static fn (MissionNote $note) => sprintf('[%s] %s', $note->getCreatedAt()->format('H:i'), $note->getText()),
                $notes,
            )),
        ]);
    }
}
