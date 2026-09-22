<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Service\DashboardData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The S3 staff home page. Anyone with dashboard.view can open it, but each section only appears
 * (and only runs its queries) when the viewer has the permission for what it shows.
 */
class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardData $data)
    {
    }

    #[Route('/s3', name: 'dashboard')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.dashboard.view');

        $can = fn (string $permission): bool => $this->isGranted('command-net-s3.' . $permission);
        $user = $this->getUser();

        return $this->render('@CommandNetS3Plugin/frontend/dashboard/index.html.twig', [
            'operations' => $can('admin.briefing.view') ? $this->data->upcomingOperations() : null,
            // Command Net data, so gated by its permission; null when the installed Command Net has no patrols.
            'patrolsMissingAar' => $this->isGranted('command-net.admin.operations.view') ? $this->data->patrolsMissingAar() : null,
            'upcomingPatrols' => $this->isGranted('command-net.admin.operations.view') ? $this->data->upcomingPatrols() : null,
            'pendingSops' => $user instanceof User && $can('sop.view') ? $this->data->pendingAcknowledgements($user) : null,
            'feedback' => $can('admin.mission.view') ? $this->data->openFeedback() : null,
            'outdatedMods' => $can('admin.server.view') ? $this->data->outdatedMods() : null,
            'activity' => $can('admin.audit_log.view') ? $this->data->recentActivity() : null,
        ]);
    }
}
