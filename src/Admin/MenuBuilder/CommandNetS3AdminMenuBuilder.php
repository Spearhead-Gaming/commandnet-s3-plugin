<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\MenuBuilder;

use Forumify\Admin\MenuBuilder\AdminMenuBuilderInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The Command Net S3 admin menu, grouped the way the spec groups the modules. Forumify drops a
 * menu item the user has no permission for, but leaves an emptied category behind as a blank
 * flyout, so categories are only added here when the user can see something in them.
 */
class CommandNetS3AdminMenuBuilder implements AdminMenuBuilderInterface
{
    /**
     * Category => icon and its pages as [label, admin route, permission].
     */
    private const array CATEGORIES = [
        'Zeus / GM' => [
            'icon' => 'ph ph-crosshair',
            'pages' => [
                ['Briefings', 'forumify_admin_command_net_s3_briefings_list', 'command-net-s3.admin.briefing.view'],
                ['Zeus Assets', 'forumify_admin_command_net_s3_zeus_assets_list', 'command-net-s3.admin.zeus_asset.view'],
                ['Operation Pages', 'forumify_admin_command_net_s3_operation_pages_list', 'command-net-s3.admin.operation_page.view'],
                ['Live Notes', 'forumify_admin_command_net_s3_live_notes', 'command-net-s3.admin.live_notes.view'],
            ],
        ],
        'Mission Development' => [
            'icon' => 'ph ph-map-trifold',
            'pages' => [
                ['SOP Library', 'forumify_admin_command_net_s3_sops_list', 'command-net-s3.admin.sop.view'],
                ['SOP Versions', 'forumify_admin_command_net_s3_sop_versions_list', 'command-net-s3.admin.sop.view'],
                ['Missions', 'forumify_admin_command_net_s3_missions_list', 'command-net-s3.admin.mission.view'],
                ['Modpacks', 'forumify_admin_command_net_s3_mod_packs_list', 'command-net-s3.admin.modpack.view'],
                ['Loadout Check', 'forumify_admin_command_net_s3_loadout_check', 'command-net-s3.admin.loadout.view'],
                ['Kit Approvals', 'forumify_admin_command_net_s3_kit_approvals_list', 'command-net-s3.admin.loadout.view'],
            ],
        ],
        'Server Administration' => [
            'icon' => 'ph ph-hard-drives',
            'pages' => [
                ['Server Status', 'forumify_admin_command_net_s3_server_status', 'command-net-s3.admin.server.view'],
                ['Game Servers', 'forumify_admin_command_net_s3_servers_list', 'command-net-s3.admin.server.view'],
                ['Server Mods', 'forumify_admin_command_net_s3_server_mods_list', 'command-net-s3.admin.server.view'],
                ['Discord Announcements', 'forumify_admin_command_net_s3_discord_settings', 'command-net-s3.admin.discord.manage'],
                ['Audit Log', 'forumify_admin_command_net_s3_audit_log', 'command-net-s3.admin.audit_log.view'],
            ],
        ],
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    public function build(Menu $menu): void
    {
        $entries = [];

        if ($this->security->isGranted('command-net-s3.dashboard.view')) {
            $entries[] = new MenuItem('Dashboard', $this->urlGenerator->generate('command_net_s3_dashboard'), [
                'icon' => 'ph ph-gauge',
                'permission' => 'command-net-s3.dashboard.view',
            ]);
        }

        foreach (self::CATEGORIES as $label => $category) {
            $items = [];
            foreach ($category['pages'] as [$pageLabel, $route, $permission]) {
                if ($this->security->isGranted($permission)) {
                    $items[] = new MenuItem($pageLabel, $this->urlGenerator->generate($route), ['permission' => $permission]);
                }
            }

            if ($items !== []) {
                $entries[] = new Menu($label, ['icon' => $category['icon']], $items);
            }
        }

        if ($entries !== []) {
            $menu->addItem(new Menu('Command Net S3', ['icon' => 'ph ph-flag-banner'], $entries));
        }
    }
}
