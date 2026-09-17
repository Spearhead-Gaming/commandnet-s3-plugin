<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3;

use Forumify\Plugin\AbstractForumifyPlugin;
use Forumify\Plugin\PluginMetadata;

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
            'MDEV ',
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
                'audit_log' => ['view'],
            ],
            'briefing' => ['view'],
            'sop' => ['view', 'acknowledge'],
        ];
    }
}
