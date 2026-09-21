<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Doctrine\ORM\EntityManagerInterface;
use MajesticDev\CommandNet\Entity\Equipment;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNet\Entity\Position;
use MajesticDev\CommandNet\Entity\Unit;
use MajesticDev\CommandNetS3\Entity\Enum\LoadoutCheckStatus;
use MajesticDev\CommandNetS3\Entity\MissionKitApproval;

/**
 * Checks a mission's kit list against what is approved. Equipment is matched by its Arma
 * class name. An item counts as approved if a Position may use it, a Unit fields it, or it has
 * been approved for this specific operation.
 */
class LoadoutChecker
{
    private const MAX_ENTRIES = 500;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Pulls class names out of pasted text: separated by whitespace, commas or semicolons,
     * de-duplicated ignoring case (Arma class names are case-insensitive).
     *
     * @return list<string>
     */
    public static function parseClassnames(string $input): array
    {
        $unique = [];
        foreach (preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $name) {
            $unique[strtolower($name)] ??= $name;
        }

        return array_slice(array_values($unique), 0, self::MAX_ENTRIES);
    }

    /**
     * @param list<string> $classnames
     * @return list<array{classname: string, equipment: ?Equipment, status: LoadoutCheckStatus, reasons: list<string>}>
     */
    public function check(Operation $operation, array $classnames): array
    {
        if ($classnames === []) {
            return [];
        }

        /** @var array<string, Equipment> $equipment */
        $equipment = [];
        /** @var list<Equipment> $found */
        $found = $this->em->createQuery(sprintf('SELECT e FROM %s e WHERE LOWER(e.classname) IN (:names)', Equipment::class))
            ->setParameter('names', array_map('strtolower', $classnames))
            ->getResult();
        foreach ($found as $item) {
            $equipment[strtolower((string)$item->getClassname())] = $item;
        }

        $reasons = $this->findReasons($operation, array_values(array_map(static fn (Equipment $e) => (int)$e->getId(), $equipment)));

        $results = [];
        foreach ($classnames as $classname) {
            $item = $equipment[strtolower($classname)] ?? null;
            $why = $item !== null ? ($reasons[$item->getId()] ?? []) : [];

            $results[] = [
                'classname' => $classname,
                'equipment' => $item,
                'status' => match (true) {
                    $item === null => LoadoutCheckStatus::UNKNOWN,
                    $why === [] => LoadoutCheckStatus::NOT_AUTHORIZED,
                    default => LoadoutCheckStatus::APPROVED,
                },
                'reasons' => $why,
            ];
        }

        return $results;
    }

    /**
     * Why each piece of equipment is approved, keyed by equipment id.
     *
     * @param list<int> $ids
     * @return array<int, list<string>>
     */
    private function findReasons(Operation $operation, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $queries = [
            'Position: %s' => sprintf('SELECT e.id AS eid, x.title AS label FROM %s x JOIN x.primaryWeapons e WHERE e.id IN (:ids)', Position::class),
            'Position: %s ' => sprintf('SELECT e.id AS eid, x.title AS label FROM %s x JOIN x.secondaryWeapons e WHERE e.id IN (:ids)', Position::class),
            'Unit: %s' => sprintf('SELECT e.id AS eid, x.name AS label FROM %s x JOIN x.vehicles e WHERE e.id IN (:ids)', Unit::class),
        ];

        $reasons = [];
        foreach ($queries as $format => $dql) {
            /** @var list<array{eid: int, label: string}> $rows */
            $rows = $this->em->createQuery($dql)->setParameter('ids', $ids)->getArrayResult();
            foreach ($rows as $row) {
                $reasons[$row['eid']][] = sprintf(trim($format), $row['label']);
            }
        }

        /** @var list<array{eid: int}> $approvals */
        $approvals = $this->em->createQuery(sprintf(
            'SELECT IDENTITY(a.equipment) AS eid FROM %s a WHERE a.operation = :operation AND IDENTITY(a.equipment) IN (:ids)',
            MissionKitApproval::class,
        ))->setParameter('operation', $operation)->setParameter('ids', $ids)->getArrayResult();
        foreach ($approvals as $row) {
            $reasons[(int)$row['eid']][] = 'Approved for this mission';
        }

        return $reasons;
    }
}
