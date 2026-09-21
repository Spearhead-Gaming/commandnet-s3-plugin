<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use MajesticDev\CommandNetS3\Service\DiscordAnnouncementSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Which S3 events post to Discord. The posting itself goes through the Discord plugin's bot
 * and its per-server announcement channels; this only holds the on/off switches.
 */
class DiscordSettingsController extends AbstractController
{
    #[Route('/command-net-s3/discord-settings', 'command_net_s3_discord_settings')]
    public function __invoke(Request $request, DiscordAnnouncementSettings $settings): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.discord.manage');

        $form = $this->createFormBuilder($settings->all())
            ->add('announceBriefings', CheckboxType::class, [
                'required' => false,
                'label' => 'Announce briefings when they are marked Ready',
            ])
            ->add('announceSopVersions', CheckboxType::class, [
                'required' => false,
                'label' => 'Announce new SOP / doctrine versions',
                'help' => 'Asks members to read and acknowledge the new version.',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            $settings->save($data);
            $this->addFlash('success', 'Discord announcement settings saved.');
            return $this->redirectToRoute('forumify_admin_command_net_s3_discord_settings');
        }

        return $this->render('@CommandNetS3Plugin/admin/discord/settings.html.twig', ['form' => $form->createView()]);
    }
}
