<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Components\Table;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\AuditLog;
use Forumify\Core\Twig\Extension\CoreRuntime;
use MajesticDev\CommandNetS3\Entity\Briefing;
use MajesticDev\CommandNetS3\Entity\MissionKitApproval;
use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Entity\SopVersion;
use MajesticDev\CommandNetS3\Entity\ZeusAsset;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Environment;

use function Symfony\Component\String\u;

/**
 * Forumify's own audit log, narrowed to this plugin's entities so S3 leadership can review
 * S3 activity without being given access to the whole site's log. Entries are written by
 * Forumify itself for any entity implementing AuditableEntityInterface, and there is no
 * way to edit or delete them from here.
 */
#[AsLiveComponent('S3AuditLogTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('command-net-s3.admin.audit_log.view')]
class S3AuditLogTable extends AbstractDoctrineTable
{
    /**
     * Add new auditable S3 entities here so they show up in this view.
     */
    private const AUDITED_ENTITIES = [Briefing::class, Sop::class, SopVersion::class, ZeusAsset::class, MissionKitApproval::class];

    public function __construct(
        private readonly CoreRuntime $coreRuntime,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        $this->sort = ['uid' => 'DESC'];
    }

    protected function getEntityClass(): string
    {
        return AuditLog::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->andWhere('e.targetEntityClass IN (:s3Entities)')
            ->setParameter('s3Entities', self::AUDITED_ENTITIES);
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('uid', [
                'field' => 'uid',
                'label' => 'Created',
                'searchable' => false,
                'renderer' => fn (Ulid $uid) => $this->coreRuntime->formatDate($uid->getDateTime()),
                'class' => 'w-15',
            ])
            ->addColumn('user', [
                'field' => 'user?.username',
                'renderer' => $this->renderUsername(...),
            ])
            ->addColumn('action', [
                'field' => 'action',
            ])
            ->addColumn('entity', [
                'field' => 'targetEntityClass',
                'renderer' => fn (?string $cls) => u($cls)->afterLast('\\')->lower()->toString(),
            ])
            ->addColumn('name', [
                'field' => 'targetName',
            ])
            ->addActionColumn($this->renderActions(...), 'uid')
        ;
    }

    private function renderUsername(?string $username): string
    {
        if (empty($username)) {
            return 'System';
        }

        $profileUrl = $this->urlGenerator->generate('forumify_forum_profile', ['username' => $username]);
        return '<a href="' . htmlspecialchars($profileUrl) . '" target="_blank">' . htmlspecialchars($username) . '</a>';
    }

    private function renderActions(Ulid $_, AuditLog $log): string
    {
        return $this->twig->render('@Forumify/admin/components/audit_log/details.html.twig', [
            'log' => $log,
        ]);
    }
}
