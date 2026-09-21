<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use MajesticDev\CommandNetS3\Admin\Form\LoadoutCheckType;
use MajesticDev\CommandNetS3\Service\LoadoutChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Lets a Mission Dev paste a mission's kit list and see what is and isn't approved before publishing.
 */
class LoadoutCheckController extends AbstractController
{
    public function __construct(private readonly LoadoutChecker $checker)
    {
    }

    #[Route('/command-net-s3/loadout-check', 'command_net_s3_loadout_check')]
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.loadout.view');

        $form = $this->createForm(LoadoutCheckType::class);
        $form->handleRequest($request);

        $results = null;
        $counts = [];
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{operation: \MajesticDev\CommandNet\Entity\Operation, classnames: string} $data */
            $data = $form->getData();
            $results = $this->checker->check($data['operation'], LoadoutChecker::parseClassnames($data['classnames']));
            foreach ($results as $result) {
                $counts[$result['status']->value] = ($counts[$result['status']->value] ?? 0) + 1;
            }
        }

        return $this->render('@CommandNetS3Plugin/admin/loadout/check.html.twig', [
            'form' => $form,
            'results' => $results,
            'counts' => $counts,
        ]);
    }
}
