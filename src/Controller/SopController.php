<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Entity\SopAcknowledgement;
use MajesticDev\CommandNetS3\Repository\SopAcknowledgementRepository;
use MajesticDev\CommandNetS3\Repository\SopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SopController extends AbstractController
{
    public function __construct(
        private readonly SopRepository $sopRepository,
        private readonly SopAcknowledgementRepository $acknowledgementRepository,
    ) {
    }

    #[Route('/sops', name: 'sop_list')]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.sop.view');

        $rows = [];
        foreach ($this->sopRepository->findBy([], ['title' => 'ASC']) as $sop) {
            $version = $sop->getCurrentVersion();
            if ($version === null) {
                continue; // nothing to read or acknowledge yet
            }

            $rows[] = [
                'sop' => $sop,
                'version' => $version,
                'acknowledged' => $this->isAcknowledged($sop),
            ];
        }

        return $this->render('@CommandNetS3Plugin/frontend/sop/list.html.twig', ['rows' => $rows]);
    }

    #[Route('/sops/{id}', name: 'sop_show', requirements: ['id' => '\d+'])]
    public function show(Sop $sop): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.sop.view');

        if ($sop->getCurrentVersion() === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CommandNetS3Plugin/frontend/sop/show.html.twig', [
            'sop' => $sop,
            'version' => $sop->getCurrentVersion(),
            'acknowledged' => $this->isAcknowledged($sop),
            'canAcknowledge' => $this->isGranted('command-net-s3.sop.acknowledge'),
        ]);
    }

    #[Route('/sops/{id}/acknowledge', name: 'sop_acknowledge', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function acknowledge(Sop $sop, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('command-net-s3.sop.acknowledge');

        $back = $this->redirectToRoute('command_net_s3_sop_show', ['id' => $sop->getId()]);

        if (!$this->isCsrfTokenValid('sop_acknowledge_' . $sop->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Your session expired, please try again.');
            return $back;
        }

        // The page may have been open while a new version was published; acknowledge only what was read.
        $version = $sop->getCurrentVersion();
        if ($version === null || $request->request->getInt('version') !== $version->getId()) {
            $this->addFlash('error', 'A new version was published. Please read it before acknowledging.');
            return $back;
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->acknowledgementRepository->hasAcknowledged($version, $user)) {
            $this->acknowledgementRepository->save(new SopAcknowledgement($version, $user));
        }

        $this->addFlash('success', 'Acknowledged.');
        return $back;
    }

    private function isAcknowledged(Sop $sop): bool
    {
        $version = $sop->getCurrentVersion();
        $user = $this->getUser();

        return $version !== null
            && $user instanceof User
            && $this->acknowledgementRepository->hasAcknowledged($version, $user);
    }
}
