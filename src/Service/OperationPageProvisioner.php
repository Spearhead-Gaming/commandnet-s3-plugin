<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Doctrine\ORM\EntityManagerInterface;
use MajesticDev\CommandNet\Entity\Enum\OperationType;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use MajesticDev\CommandNetS3\Repository\OperationPageRepository;

/**
 * Creates an Operation's information page and gives it the next order number, so nobody has to
 * add a page by hand for each operation. The page starts empty and unpublished; staff edit it
 * like any other.
 *
 * Order numbers count within a Deployment: "26-10-03" is the third operation of the Deployment
 * that starts in October 2026. An operation with no Deployment is numbered within its year
 * instead ("26-04"). Numbers are handed out in the order pages are created, and the Deployment
 * generator creates operations by date, so a generated Deployment comes out in date order.
 */
class OperationPageProvisioner
{
    /**
     * Only the staff-run operations get a page. Patrols, fun days, trainings and meetings have
     * no OPORD to publish.
     *
     * @var list<OperationType>
     */
    private const array TYPES = [OperationType::OPERATION];

    /**
     * Highest number handed out by this instance, per Deployment and prefix. Pages created in
     * the same flush are not in the database yet, so a bulk generate would otherwise give every
     * one of them the same number.
     *
     * @var array<string, int>
     */
    private array $issued = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OperationPageRepository $pages,
    ) {
    }

    /**
     * @return list<string> the operation type values that get a page, for queries
     */
    public static function typeValues(): array
    {
        return array_map(static fn (OperationType $type) => $type->value, self::TYPES);
    }

    public function isEligible(Operation $operation): bool
    {
        return in_array($operation->getType(), self::TYPES, true);
    }

    /**
     * Persists a new page for the operation. Does not flush.
     */
    public function provision(Operation $operation): OperationPage
    {
        $page = new OperationPage();
        $page->setOperation($operation);
        $page->setOrderNumber($this->nextOrderNumber($operation));
        $this->em->persist($page);

        return $page;
    }

    public function nextOrderNumber(Operation $operation): string
    {
        // Command Net only has Deployments from 1.1.0; on an older install every operation is
        // numbered within its year.
        $deployment = method_exists($operation, 'getDeployment') ? $operation->getDeployment() : null;

        $prefix = $deployment !== null
            ? $deployment->getStartDate()->format('y-m')
            : $operation->getStartDateTime()->format('y');
        $key = ($deployment !== null ? 'd' . spl_object_id($deployment) : 'none') . ':' . $prefix;

        $next = max($this->pages->highestSequence($deployment, $prefix), $this->issued[$key] ?? 0) + 1;
        $this->issued[$key] = $next;

        return self::format($prefix, $next);
    }

    public static function format(string $prefix, int $sequence): string
    {
        return sprintf('%s-%02d', $prefix, $sequence);
    }
}
