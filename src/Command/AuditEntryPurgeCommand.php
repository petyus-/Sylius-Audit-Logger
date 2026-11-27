<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Command;

use Petyus\SyliusAuditLogger\AuditEntryRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: AuditEntryPurgeCommand::NAME,
    description: 'Deletes audit entries older than the configured retention period.',
)]
final class AuditEntryPurgeCommand extends Command
{
    public const NAME = 'petyus:audit-logger:purge';

    public function __construct(
        private readonly AuditEntryRepositoryInterface $repository,
        private readonly int $retentionDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Retention period in days (overrides the configured retention_days).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report how many entries would be deleted.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $days */
        $days = $input->getOption('days');
        $retentionDays = null !== $days ? (int) $days : $this->retentionDays;

        if ($retentionDays <= 0) {
            $io->note('Retention is disabled (retention_days is 0). Nothing was purged.');

            return Command::SUCCESS;
        }

        $threshold = new \DateTimeImmutable(sprintf('-%d days', $retentionDays));

        if (true === $input->getOption('dry-run')) {
            $count = $this->repository->countOlderThan($threshold);
            $io->success(sprintf('%d audit entr%s older than %d day%s would be deleted (dry-run).', $count, 1 === $count ? 'y' : 'ies', $retentionDays, 1 === $retentionDays ? '' : 's'));

            return Command::SUCCESS;
        }

        $deleted = $this->repository->deleteOlderThan($threshold);
        $io->success(sprintf('Deleted %d audit entr%s older than %d day%s.', $deleted, 1 === $deleted ? 'y' : 'ies', $retentionDays, 1 === $retentionDays ? '' : 's'));

        return Command::SUCCESS;
    }
}
