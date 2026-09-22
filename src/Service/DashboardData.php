<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Forumify\Core\Entity\AuditLog;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\AuditLogRepository;
use MajesticDev\CommandNet\Entity\Enum\AarStatus;
use MajesticDev\CommandNet\Entity\Enum\OperationStatus;
use MajesticDev\CommandNet\Entity\Enum\OperationType;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNet\Repository\OperationRepository;
use MajesticDev\CommandNet\Service\EventRules;
use MajesticDev\CommandNetS3\Admin\Components\Table\S3AuditLogTable;
use MajesticDev\CommandNetS3\Entity\Briefing;
use MajesticDev\CommandNetS3\Entity\MissionFeedback;
use MajesticDev\CommandNetS3\Entity\ServerMod;
use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Repository\BriefingRepository;
use MajesticDev\CommandNetS3\Repository\MissionFeedbackRepository;
use MajesticDev\CommandNetS3\Repository\ServerModRepository;
use MajesticDev\CommandNetS3\Repository\SopAcknowledgementRepository;
use MajesticDev\CommandNetS3\Repository\SopRepository;

/**
 * The numbers and short lists behind the S3 dashboard. Every method is a small read-only query
 * and stays cheap on purpose, so the page is fast enough to be someone's start page.
 *
 * ponytail: the out-of-date mod list and the acknowledgement check load whole tables and filter
 * in PHP. Fine for a community's few dozen mods and documents; push it into SQL if that grows.
 */
class DashboardData
{
    private const int LIST_LIMIT = 6;

    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly BriefingRepository $briefingRepository,
        private readonly SopRepository $sopRepository,
        private readonly SopAcknowledgementRepository $acknowledgementRepository,
        private readonly MissionFeedbackRepository $feedbackRepository,
        private readonly ServerModRepository $modRepository,
        private readonly AuditLogRepository $auditLogRepository,
    ) {
    }

    /**
     * Operations coming up (or running now), each with its briefing if it has one.
     *
     * @return list<array{operation: Operation, briefing: ?Briefing}>
     */
    public function upcomingOperations(): array
    {
        $qb = $this->operationRepository->createQueryBuilder('o')
            ->where('o.startDateTime >= :since')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('since', new \DateTime('-1 day'))
            ->setParameter('statuses', [OperationStatus::SCHEDULED->value, OperationStatus::IN_PROGRESS->value])
            ->orderBy('o.startDateTime', 'ASC')
            ->setMaxResults(self::LIST_LIMIT);
        // Patrols have their own card below, and no briefing.
        if ($this->supportsPatrols()) {
            $qb->andWhere('o.type != :patrol')->setParameter('patrol', OperationType::PATROL->value);
        }

        /** @var list<Operation> $operations */
        $operations = $qb->getQuery()->getResult();

        return array_map(fn (Operation $operation) => [
            'operation' => $operation,
            'briefing' => $this->briefingRepository->findOneBy(['operation' => $operation]),
        ], $operations);
    }

    /**
     * Patrols coming up, soonest first. Null when the installed Command Net has no patrols
     * yet, so the dashboard just leaves the card out.
     *
     * @return list<Operation>|null
     */
    public function upcomingPatrols(): ?array
    {
        if (!$this->supportsPatrols()) {
            return null;
        }

        return array_values($this->operationRepository->findUpcomingPatrols(self::LIST_LIMIT));
    }

    /**
     * Patrols that have ended with no after-action report, oldest first, each flagged overdue
     * once past its deadline. "count" is all of them, "rows" the first few. Null when the
     * installed Command Net has no patrols yet.
     *
     * @return array{count: int, rows: list<array{patrol: Operation, overdue: bool, dueAt: \DateTimeImmutable}>}|null
     */
    public function patrolsMissingAar(): ?array
    {
        if (!$this->supportsPatrols()) {
            return null;
        }

        // EventRules has no dependencies, so it is built here rather than injected: injecting it
        // would stop the container compiling on a Command Net that does not have it.
        $rules = new EventRules();
        $now = new \DateTimeImmutable();
        $patrols = $this->operationRepository->findPatrolsAwaitingAar(null, $now);

        return [
            'count' => count($patrols),
            'rows' => array_map(static fn (Operation $patrol) => [
                'patrol' => $patrol,
                'overdue' => $rules->aarStatus($patrol, $now) === AarStatus::OVERDUE,
                'dueAt' => $rules->aarDueAt($patrol),
            ], array_slice($patrols, 0, self::LIST_LIMIT)),
        ];
    }

    /**
     * Patrol support arrived in Command Net after this plugin first shipped, so check the
     * installed version has all of it before touching any of it.
     */
    private function supportsPatrols(): bool
    {
        return class_exists(EventRules::class)
            && enum_exists(AarStatus::class)
            && OperationType::tryFrom('patrol') !== null
            && method_exists($this->operationRepository, 'findPatrolsAwaitingAar')
            && method_exists($this->operationRepository, 'findUpcomingPatrols');
    }

    /**
     * SOP documents whose current version this user has not acknowledged yet.
     *
     * @return list<Sop>
     */
    public function pendingAcknowledgements(User $user): array
    {
        $pending = [];
        foreach ($this->sopRepository->findBy([], ['title' => 'ASC']) as $sop) {
            $version = $sop->getCurrentVersion();
            if ($version !== null && !$this->acknowledgementRepository->hasAcknowledged($version, $user)) {
                $pending[] = $sop;
            }
        }

        return $pending;
    }

    /**
     * @return array{count: int, latest: list<MissionFeedback>}
     */
    public function openFeedback(): array
    {
        return [
            'count' => $this->feedbackRepository->count(['resolved' => false]),
            'latest' => $this->feedbackRepository->findBy(['resolved' => false], ['id' => 'DESC'], self::LIST_LIMIT),
        ];
    }

    /**
     * @return list<ServerMod>
     */
    public function outdatedMods(): array
    {
        return array_values(array_filter(
            $this->modRepository->findAll(),
            static fn (ServerMod $mod) => $mod->isOutdated(),
        ));
    }

    /**
     * The latest S3 entries in Forumify's audit log, newest first.
     *
     * @return list<AuditLog>
     */
    public function recentActivity(): array
    {
        /** @var list<AuditLog> */
        return $this->auditLogRepository->createQueryBuilder('a')
            ->where('a.targetEntityClass IN (:entities)')
            ->setParameter('entities', S3AuditLogTable::AUDITED_ENTITIES)
            ->orderBy('a.uid', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }
}
