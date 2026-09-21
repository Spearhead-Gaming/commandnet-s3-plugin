<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3;

use Forumify\Plugin\AbstractForumifyPlugin;
use Forumify\Plugin\PluginMetadata;
use MajesticDev\Discord\CommandNetDiscordPlugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * The entry point forumify uses to recognise this package as a plugin.
 *
 * Permissions are checked as "<slugged-plugin-name>.<area>.<action>", e.g.
 * "command-net-s3.admin.briefing.manage".
 *
 * Modules land here as they're built (see Plugin Spec.md's roadmap): Phase 1 is the
 * briefing tool, SOP/doctrine library, and audit log.
 */
class CommandNetS3Plugin extends AbstractForumifyPlugin
{
    public function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            'Command Net S3',
            'MajesticDev ',
            'S3 (Operations) tooling for Zeus/GM support, mission development, and server administration.',
            'https://example.com', // TODO: replace with real domain once purchased
        );
    }

    public function getPermissions(): array
    {
        return [
            'admin' => [
                'briefing' => ['view', 'manage'],
                'sop' => ['view', 'manage'],
                'zeus_asset' => ['view', 'manage'],
                'loadout' => ['view', 'manage'],
                'server' => ['view', 'manage'],
                'mission' => ['view', 'manage', 'manage_all'],
                'live_notes' => ['view', 'manage'],
                'audit_log' => ['view'],
                'discord' => ['manage'],
            ],
            'briefing' => ['view'],
            'sop' => ['view', 'acknowledge'],
            'mission' => ['feedback'],
            'dashboard' => ['view'],
        ];
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        // The Discord plugin is optional; the announcers only exist when it is installed.
        /** @var array<string, class-string> $bundles */
        $bundles = $builder->getParameter('kernel.bundles');
        if (in_array(CommandNetDiscordPlugin::class, $bundles, true)) {
            $container->import($this->getPath() . '/config/discord.php');
        }
    }
}
