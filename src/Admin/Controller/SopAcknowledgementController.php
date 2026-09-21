<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Repository\SopAcknowledgementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Staff view of who has and hasn't acknowledged a document's current version.
 */
class SopAcknowledgementController extends AbstractController
{
    public function __construct(private readonly SopAcknowledgementRepository $acknowledgementRepository)
    {
    }

    #[Route('/command-net-s3/sops/{id}/acknowledgements', 'command_net_s3_sop_acknowledgements', requirements: ['id' => '\d+'])]
    public function __invoke(Sop $sop): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.sop.view');

        $version = $sop->getCurrentVersion();

        return $this->render('@CommandNetS3Plugin/admin/sop/acknowledgements.html.twig', [
            'sop' => $sop,
            'version' => $version,
            'pending' => $version !== null ? $this->acknowledgementRepository->findPendingSoldiers($version) : [],
            'acknowledgements' => $version !== null
                ? $this->acknowledgementRepository->findBy(['sopVersion' => $version], ['createdAt' => 'DESC'])
                : [],
        ]);
    }
}
