<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use MajesticDev\CommandNetS3\Admin\Form\SharedPageFields;
use MajesticDev\CommandNetS3\Service\PageDefaultsSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The S3-wide defaults for operation pages: the last fallback after the page's own values and its
 * Deployment's details. Same permissions as Operation Pages.
 */
class PageDefaultsController extends AbstractController
{
    #[Route('/command-net-s3/page-defaults', 'command_net_s3_page_defaults')]
    public function __invoke(Request $request, PageDefaultsSettings $settings): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.operation_page.view');

        $canManage = $this->isGranted('command-net-s3.admin.operation_page.manage');
        $builder = $this->createFormBuilder($settings->all(), ['disabled' => !$canManage]);
        SharedPageFields::add($builder);
        $form = $builder->getForm()->handleRequest($request);

        if ($canManage && $form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            $settings->save($data);
            $this->addFlash('success', 'Page defaults saved.');
            return $this->redirectToRoute('forumify_admin_command_net_s3_page_defaults');
        }

        return $this->render('@CommandNetS3Plugin/admin/page_defaults/settings.html.twig', [
            'form' => $form->createView(),
            'canManage' => $canManage,
        ]);
    }
}
