<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Admin\Form\ModPackVersionType;
use MajesticDev\CommandNetS3\Entity\ModPack;
use MajesticDev\CommandNetS3\Entity\ModPackVersion;
use MajesticDev\CommandNetS3\Service\ModListParser;
use MajesticDev\CommandNetS3\Service\ModPackService;
use MajesticDev\CommandNetS3\Service\ParsedMod;
use MajesticDev\CommandNetS3\Service\PresetRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A modpack's page: every version with its changelog and a download of that version's launcher
 * preset (which is how you roll back), plus publishing a new version. Publishing needs
 * modpack.manage; looking and downloading needs modpack.view.
 */
class ModPackDetailController extends AbstractController
{
    public function __construct(
        private readonly ModPackService $service,
        private readonly PresetRenderer $presetRenderer,
    ) {
    }

    #[Route('/command-net-s3/mod-packs/{id}', 'command_net_s3_mod_pack', requirements: ['id' => '\d+'])]
    public function detail(ModPack $pack): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.modpack.view');

        $versions = $pack->getVersions()->toArray();

        return $this->render('@CommandNetS3Plugin/admin/modpack/detail.html.twig', [
            'pack' => $pack,
            'versions' => $versions,
            'current' => array_map(static fn (ParsedMod $mod) => [
                'name' => $mod->name,
                'dlc' => $mod->kind->value === 'dlc',
                'url' => ModListParser::url($mod->kind, $mod->steamId),
            ], $this->service->mods($versions[0] ?? null)),
            'canManage' => $this->isGranted('command-net-s3.admin.modpack.manage'),
        ]);
    }

    #[Route('/command-net-s3/mod-packs/{id}/publish', 'command_net_s3_mod_pack_publish', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function publish(ModPack $pack, Request $request): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.modpack.manage');

        // Start from the current list, so an update is an edit rather than a retype.
        $latest = $this->service->latest($pack);
        $form = $this->createForm(ModPackVersionType::class, null, [
            'mod_text' => ModListParser::toText($this->service->mods($latest)),
        ]);
        $form->handleRequest($request);
        $previewed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{label: string, changelog: ?string, mods: array{mods: list<ParsedMod>}} $data */
            $data = $form->getData();
            $mods = $data['mods']['mods'];
            $preview = $form->get('preview');

            if ($mods === []) {
                $form->get('mods')->addError(new FormError('Upload a launcher preset or type at least one mod.'));
            } elseif ($preview instanceof SubmitButton && $preview->isClicked()) {
                // Show the form again with the parsed list and the drafted changelog; nothing is saved.
                $form = $this->createForm(ModPackVersionType::class, [
                    'label' => $data['label'],
                    'changelog' => $this->service->draftChangelog($pack, $mods),
                ], ['mod_text' => ModListParser::toText($mods)]);
                $previewed = true;
            } else {
                $user = $this->getUser();
                $changelog = trim((string)$data['changelog']);
                $this->service->publish(
                    $pack,
                    $data['label'],
                    $changelog !== '' ? $changelog : $this->service->draftChangelog($pack, $mods),
                    $mods,
                    $user instanceof User ? $user : null,
                );

                $this->addFlash('success', 'Version published.');
                return $this->redirectToRoute('forumify_admin_command_net_s3_mod_pack', ['id' => $pack->getId()]);
            }
        }

        return $this->render('@CommandNetS3Plugin/admin/modpack/publish.html.twig', [
            'pack' => $pack,
            'form' => $form->createView(),
            'previewed' => $previewed,
            'hasPrevious' => $latest !== null,
        ]);
    }

    #[Route('/command-net-s3/mod-packs/versions/{id}/preset.html', 'command_net_s3_mod_pack_preset', requirements: ['id' => '\d+'])]
    public function preset(ModPackVersion $version): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.modpack.view');

        return $this->presetRenderer->download($version->getModPack()->getName() . ' ' . $version->getLabel(), $this->service->mods($version));
    }
}
