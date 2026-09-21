<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Enum\RsvpStatus;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNet\Repository\SoldierProfileRepository;
use MajesticDev\CommandNetS3\Repository\BriefingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BriefingController extends AbstractController
{
    public function __construct(
        private readonly BriefingRepository $briefingRepository,
        private readonly SoldierProfileRepository $soldierProfileRepository,
    ) {
    }

    #[Route('/operations/{id}/briefing', name: 'briefing', requirements: ['id' => '\d+'])]
    public function __invoke(Operation $operation): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.briefing.view');

        $briefing = $this->briefingRepository->findOneBy(['operation' => $operation]);
        $isStaff = $this->isGranted('command-net-s3.admin.briefing.manage');

        // A missing, still-Draft, or not-yours briefing all look the same: not found.
        if ($briefing === null || !($isStaff || ($briefing->getStatus()->isVisibleToPlayers() && $this->isSlotted($operation)))) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CommandNetS3Plugin/frontend/briefing/show.html.twig', [
            'operation' => $operation,
            'briefing' => $briefing,
        ]);
    }

    /**
     * Command Net has no slotting model, only RSVPs, so "slotted" means an Attending or Maybe RSVP.
     */
    private function isSlotted(Operation $operation): bool
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $profile = $user !== null ? $this->soldierProfileRepository->findOneBy(['user' => $user]) : null;
        $status = $profile !== null ? $operation->getRsvpFor($profile)?->getStatus() : null;

        return $status === RsvpStatus::ATTENDING || $status === RsvpStatus::MAYBE;
    }
}
