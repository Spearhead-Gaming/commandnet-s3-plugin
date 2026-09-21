<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuditLogController extends AbstractController
{
    #[Route('/command-net-s3/audit-log', 'command_net_s3_audit_log')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('command-net-s3.admin.audit_log.view');

        return $this->render('@CommandNetS3Plugin/admin/audit_log/list.html.twig');
    }
}
