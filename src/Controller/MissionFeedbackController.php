<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Enum\FeedbackKind;
use MajesticDev\CommandNetS3\Entity\MissionFeedback;
use MajesticDev\CommandNetS3\Repository\MissionFeedbackRepository;
use MajesticDev\CommandNetS3\Repository\MissionVersionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Where a playtester reports a bug, balance issue or feedback on one mission version. Reached
 * only through the link a Mission Dev shares (it carries a random token), and deliberately
 * not listed anywhere, so members can't browse upcoming missions.
 */
class MissionFeedbackController extends AbstractController
{
    public function __construct(
        private readonly MissionVersionRepository $versionRepository,
        private readonly MissionFeedbackRepository $feedbackRepository,
    ) {
    }

    #[Route('/missions/feedback/{token}', name: 'mission_feedback', requirements: ['token' => '[a-f0-9]{32}'])]
    public function __invoke(string $token, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.mission.feedback');

        $version = $this->versionRepository->findOneBy(['feedbackToken' => $token]);
        if ($version === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('kind', EnumType::class, [
                'class' => FeedbackKind::class,
                'label' => 'What kind of feedback is this?',
                'choice_label' => fn (FeedbackKind $kind) => $kind->label(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Details',
                'help' => 'What happened, where in the mission, and how to reproduce it if you can.',
                'attr' => ['rows' => 8],
                'constraints' => [new NotBlank(), new Length(min: 5, max: 5000)],
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{kind: FeedbackKind, description: string} $data */
            $data = $form->getData();
            $user = $this->getUser();

            $feedback = new MissionFeedback($version, $user instanceof User ? $user : null);
            $feedback->setKind($data['kind']);
            $feedback->setDescription(trim($data['description']));
            $this->feedbackRepository->save($feedback);

            $this->addFlash('success', 'Thanks, your feedback was sent to the mission developer.');
            return $this->redirectToRoute('command_net_s3_mission_feedback', ['token' => $token]);
        }

        return $this->render('@CommandNetS3Plugin/frontend/mission/feedback.html.twig', [
            'version' => $version,
            'form' => $form->createView(),
        ]);
    }
}
