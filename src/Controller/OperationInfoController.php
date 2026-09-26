<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use MajesticDev\CommandNet\Entity\Enum\OperationStatus;
use MajesticDev\CommandNet\Entity\Enum\RsvpStatus;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\Enum\ModKind;
use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Entity\ModPackVersion;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use MajesticDev\CommandNetS3\Entity\ServerMod;
use MajesticDev\CommandNetS3\Repository\BriefingRepository;
use MajesticDev\CommandNetS3\Repository\MissionRepository;
use MajesticDev\CommandNetS3\Repository\OperationPageRepository;
use MajesticDev\CommandNetS3\Repository\ServerModRepository;
use MajesticDev\CommandNetS3\Service\CurrentOperationFinder;
use MajesticDev\CommandNetS3\Service\MissionModList;
use MajesticDev\CommandNetS3\Service\ModListParser;
use MajesticDev\CommandNetS3\Service\ModPackService;
use MajesticDev\CommandNetS3\Service\OperationPageDetails;
use MajesticDev\CommandNetS3\Service\OperationPageParser as Parser;
use MajesticDev\CommandNetS3\Service\ParsedMod;
use MajesticDev\CommandNetS3\Service\PresetRenderer;
use MajesticDev\CommandNetS3\Service\ServerQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The operation information page. Everything is read when the page is requested, so it always
 * shows the operation's current details, RSVPs, briefing, mods and server state; the only
 * stored page content is what staff typed into the Operation Page.
 *
 * The mod list is the one on the newest mission built for the operation that has one (typed, or
 * from an uploaded launcher preset), else the current version of its deployment's modpack, else
 * the mods tracked on the operation's server. Players download a launcher preset generated from
 * that same list.
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
        private readonly MissionRepository $missionRepository,
        private readonly MissionModList $missionModList,
        private readonly ModPackService $modPackService,
        private readonly PresetRenderer $presetRenderer,
        private readonly ServerQuery $serverQuery,
        private readonly OperationPageDetails $pageDetails,
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

    #[Route('/operations/{id}/modpack.html', name: 'operation_modpack', requirements: ['id' => '\d+'])]
    public function modpack(Operation $operation): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.operation_info.view');

        $mods = $this->modsFor($operation, $this->pageDetails->forPage($this->visiblePage($operation))['server']);
        if ($mods['parsed'] === []) {
            throw $this->createNotFoundException('This operation has no mod list.');
        }

        return $this->presetRenderer->download($mods['name'], $mods['parsed']);
    }

    private function page(?Operation $operation): Response
    {
        if ($operation === null) {
            return $this->render('@CommandNetS3Plugin/frontend/operation_info/show.html.twig', ['operation' => null]);
        }

        $isStaff = $this->isGranted('command-net-s3.admin.operation_page.manage');

        $page = $this->visiblePage($operation);
        $unpublished = $isStaff && $page !== null && !$page->isPublished();

        // Draft briefings are only for staff.
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

        // The page's own value, else its Deployment's, else the S3-wide default.
        $details = $this->pageDetails->forPage($page);
        $server = $details['server'];
        $mods = $this->modsFor($operation, $server);

        // A link typed on the Operation Page wins; otherwise the preset generated from the list.
        $presetUrl = Parser::safeUrl($page?->getPresetUrl())
            ?? ($mods['parsed'] !== [] ? $this->generateUrl('command_net_s3_operation_modpack', ['id' => $operation->getId()]) : null);

        return $this->render('@CommandNetS3Plugin/frontend/operation_info/show.html.twig', [
            'operation' => $operation,
            'page' => $page,
            'briefing' => $briefing,
            'preview' => $unpublished,
            'summary' => $summary,
            'timeline' => Parser::rows($page?->getTimeline(), 3),
            'taskOrg' => Parser::taskOrg($page?->getTaskOrg()),
            'missionData' => Parser::rows($page?->getMissionData(), 2),
            'comms' => Parser::comms($details['comms']),
            'roe' => Parser::lines($details['roe']),
            'checklist' => Parser::lines($details['checklist']),
            'quickLinks' => Parser::links($details['quickLinks']),
            'presetUrl' => $presetUrl,
            'steamUrl' => Parser::safeUrl($details['steamCollectionUrl']),
            'server' => $server,
            'serverInfo' => $server !== null ? $this->serverQuery->query($server) : null,
            'mods' => $mods['rows'],
            'modpack' => $mods['pack'] !== null ? [
                'name' => $mods['pack']->getModPack()->getName(),
                'label' => $mods['pack']->getLabel(),
                'changes' => mb_strimwidth(trim((string)$mods['pack']->getChangelog()), 0, 600, '…'),
                'date' => $mods['pack']->getCreatedAt(),
            ] : null,
            'attending' => $attending,
            'maybe' => $maybe,
            'showCountdown' => in_array($operation->getStatus(), [OperationStatus::SCHEDULED, OperationStatus::IN_PROGRESS], true),
        ]);
    }

    /**
     * The operation's page content, unless it is unpublished and the viewer isn't staff.
     */
    private function visiblePage(Operation $operation): ?OperationPage
    {
        $page = $this->pageRepository->findOneBy(['operation' => $operation]);
        if ($page !== null && !$page->isPublished() && !$this->isGranted('command-net-s3.admin.operation_page.manage')) {
            return null;
        }

        return $page;
    }

    /**
     * The operation's mods, as the list to generate a preset from and the rows the page shows.
     * In order: the newest mission for the operation that has a mod list (its own override), then
     * the current version of its deployment's modpack, then the mods tracked on its server.
     *
     * @return array{parsed: list<ParsedMod>, rows: list<array{name: string, dlc: bool, url: ?string, version: ?string}>, name: string, pack: ?ModPackVersion}
     */
    private function modsFor(Operation $operation, ?GameServer $server): array
    {
        foreach ($this->missionRepository->findBy(['operation' => $operation], ['id' => 'DESC']) as $mission) {
            $parsed = $this->missionModList->forMission($mission);
            if ($parsed !== []) {
                return $this->fromList($parsed, $operation->getTitle(), null);
            }
        }

        $pack = $this->modPackService->currentFor($operation->getDeployment());
        $parsed = $this->modPackService->mods($pack);
        if ($pack !== null && $parsed !== []) {
            return $this->fromList($parsed, $pack->getModPack()->getName() . ' ' . $pack->getLabel(), $pack);
        }

        /** @var list<ServerMod> $serverMods */
        $serverMods = $server !== null ? $this->modRepository->findBy(['server' => $server], ['name' => 'ASC']) : [];

        return [
            'name' => $operation->getTitle(),
            'pack' => null,
            'parsed' => array_map(static fn (ServerMod $mod) => new ParsedMod($mod->getName(), ModKind::MOD, $mod->getWorkshopId()), $serverMods),
            'rows' => array_map(static fn (ServerMod $mod) => [
                'name' => $mod->getName(),
                'dlc' => false,
                'url' => ModListParser::url(ModKind::MOD, $mod->getWorkshopId()),
                'version' => $mod->getInstalledVersion(),
            ], $serverMods),
        ];
    }

    /**
     * @param list<ParsedMod> $parsed
     * @return array{parsed: list<ParsedMod>, rows: list<array{name: string, dlc: bool, url: ?string, version: ?string}>, name: string, pack: ?ModPackVersion}
     */
    private function fromList(array $parsed, string $name, ?ModPackVersion $pack): array
    {
        return [
            'name' => $name,
            'pack' => $pack,
            'parsed' => $parsed,
            'rows' => array_map(static fn (ParsedMod $mod) => [
                'name' => $mod->name,
                'dlc' => $mod->kind === ModKind::DLC,
                'url' => ModListParser::url($mod->kind, $mod->steamId),
                'version' => null,
            ], $parsed),
        ];
    }
}
