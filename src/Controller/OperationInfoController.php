<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use MajesticDev\CommandNet\Entity\Enum\OperationStatus;
use MajesticDev\CommandNet\Entity\Enum\RsvpStatus;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Repository\BriefingRepository;
use MajesticDev\CommandNetS3\Repository\OperationPageRepository;
use MajesticDev\CommandNetS3\Repository\ServerModRepository;
use MajesticDev\CommandNetS3\Service\CurrentOperationFinder;
use MajesticDev\CommandNetS3\Service\OperationPageParser as Parser;
use MajesticDev\CommandNetS3\Service\ServerQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The operation information page. Everything is read when the page is requested, so it always
 * shows the operation's current details, RSVPs, briefing, mods and server state; the only
 * stored page content is what staff typed into the Operation Page.
 *
 * ponytail: the server is queried on every view (up to a couple of seconds if it is down). Cache
 * the result for a short time if this page gets busy.
 */
class OperationInfoController extends AbstractController
{
    public function __construct(
        private readonly CurrentOperationFinder $finder,
        private readonly OperationPageRepository $pageRepository,
        private readonly BriefingRepository $briefingRepository,
        private readonly ServerModRepository $modRepository,
        private readonly ServerQuery $serverQuery,
    ) {
    }

    #[Route('/operations/current', name: 'operation_current')]
    public function current(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.operation_info.view');

        return $this->page($this->finder->find());
    }

    #[Route('/operations/{id}/info', name: 'operation_info', requirements: ['id' => '\d+'])]
    public function show(Operation $operation): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.operation_info.view');

        return $this->page($operation);
    }

    private function page(?Operation $operation): Response
    {
        if ($operation === null) {
            return $this->render('@CommandNetS3Plugin/frontend/operation_info/show.html.twig', ['operation' => null]);
        }

        $isStaff = $this->isGranted('command-net-s3.admin.operation_page.manage');

        // Unpublished page content and draft briefings are only for staff; everyone else still
        // gets the operation's own details.
        $page = $this->pageRepository->findOneBy(['operation' => $operation]);
        $unpublished = $page !== null && !$page->isPublished();
        if ($unpublished && !$isStaff) {
            $page = null;
        }

        $briefing = $this->briefingRepository->findOneBy(['operation' => $operation]);
        if ($briefing !== null && !$isStaff && !$briefing->getStatus()->isVisibleToPlayers()) {
            $briefing = null;
        }

        $attending = 0;
        $maybe = 0;
        foreach ($operation->getRsvps() as $rsvp) {
            match ($rsvp->getStatus()) {
                RsvpStatus::ATTENDING => $attending++,
                RsvpStatus::MAYBE => $maybe++,
                default => null,
            };
        }

        $summary = $page?->getSummary();
        if (($summary === null || $summary === '') && $briefing !== null) {
            $summary = mb_strimwidth(trim(strip_tags($briefing->getTaskPurpose())), 0, 300, '…');
        }

        $server = $page?->getServer();

        return $this->render('@CommandNetS3Plugin/frontend/operation_info/show.html.twig', [
            'operation' => $operation,
            'page' => $page,
            'briefing' => $briefing,
            'preview' => $unpublished && $isStaff,
            'summary' => $summary,
            'timeline' => Parser::rows($page?->getTimeline(), 3),
            'taskOrg' => Parser::taskOrg($page?->getTaskOrg()),
            'missionData' => Parser::rows($page?->getMissionData(), 2),
            'comms' => Parser::rows($page?->getComms(), 3),
            'roe' => Parser::lines($page?->getRoe()),
            'checklist' => Parser::lines($page?->getChecklist()),
            'quickLinks' => Parser::links($page?->getQuickLinks()),
            'presetUrl' => Parser::safeUrl($page?->getPresetUrl()),
            'steamUrl' => Parser::safeUrl($page?->getSteamCollectionUrl()),
            'server' => $server,
            'serverInfo' => $server !== null ? $this->serverQuery->query($server) : null,
            'mods' => $server !== null ? $this->modRepository->findBy(['server' => $server], ['name' => 'ASC']) : [],
            'attending' => $attending,
            'maybe' => $maybe,
            'showCountdown' => in_array($operation->getStatus(), [OperationStatus::SCHEDULED, OperationStatus::IN_PROGRESS], true),
        ]);
    }
}
