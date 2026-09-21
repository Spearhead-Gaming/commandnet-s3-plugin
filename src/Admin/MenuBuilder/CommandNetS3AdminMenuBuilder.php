<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\MenuBuilder;

use Forumify\Admin\MenuBuilder\AdminMenuBuilderInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommandNetS3AdminMenuBuilder implements AdminMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(Menu $menu): void
    {
        $url = fn (string $route): string => $this->urlGenerator->generate($route);

        $menu->addItem(new Menu('Command Net S3', ['icon' => 'ph ph-flag-banner'], [
            new MenuItem('Briefings', $url('forumify_admin_command_net_s3_briefings_list'), [
                'permission' => 'command-net-s3.admin.briefing.view',
            ]),
            new MenuItem('SOP Library', $url('forumify_admin_command_net_s3_sops_list'), [
                'permission' => 'command-net-s3.admin.sop.view',
            ]),
            new MenuItem('SOP Versions', $url('forumify_admin_command_net_s3_sop_versions_list'), [
                'permission' => 'command-net-s3.admin.sop.view',
            ]),
            new MenuItem('Zeus Assets', $url('forumify_admin_command_net_s3_zeus_assets_list'), [
                'permission' => 'command-net-s3.admin.zeus_asset.view',
            ]),
            new MenuItem('Audit Log', $url('forumify_admin_command_net_s3_audit_log'), [
                'permission' => 'command-net-s3.admin.audit_log.view',
            ]),
        ]));
    }
}
