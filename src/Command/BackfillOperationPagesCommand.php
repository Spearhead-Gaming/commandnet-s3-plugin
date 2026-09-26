<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Command;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use MajesticDev\CommandNetS3\Service\OperationPageProvisioner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * New operations get their page automatically (see CreateOperationPageListener); this is for the
 * ones that existed before that. Oldest first, so the numbers follow the calendar.
 */
#[AsCommand('command-net-s3:operation-pages:backfill', 'Create the missing Operation Pages for existing operations, numbered per Deployment.')]
class BackfillOperationPagesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OperationPageProvisioner $provisioner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Include operations that have already started (default: only upcoming ones).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without saving anything.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Operation::class, 'o')
            ->leftJoin(OperationPage::class, 'p', 'WITH', 'p.operation = o')
            ->where('p.id IS NULL')
            ->andWhere('o.type IN (:types)')
            ->setParameter('types', OperationPageProvisioner::typeValues())
            ->orderBy('o.startDateTime', 'ASC');

        if (!$input->getOption('all')) {
            $qb->andWhere('o.startDateTime >= :now')->setParameter('now', new DateTime());
        }

        /** @var list<Operation> $operations */
        $operations = $qb->getQuery()->getResult();
        if ($operations === []) {
            $io->success('Every matching operation already has a page.');
            return Command::SUCCESS;
        }

        $dryRun = (bool)$input->getOption('dry-run');
        $rows = [];
        foreach ($operations as $operation) {
            $page = $this->provisioner->provision($operation);
            $rows[] = [$page->getOrderNumber(), $operation->getStartDateTime()->format('Y-m-d H:i'), $operation->getTitle()];
            if ($dryRun) {
                $this->em->detach($page);
            }
        }

        $io->table(['Order number', 'Starts', 'Operation'], $rows);

        if ($dryRun) {
            $io->note(sprintf('Dry run: %d page(s) would be created. Nothing was saved.', count($rows)));
            return Command::SUCCESS;
        }

        $this->em->flush();
        $io->success(sprintf('Created %d page(s). They are unpublished, so only staff see them until you publish.', count($rows)));

        return Command::SUCCESS;
    }
}
