<?php

declare(strict_types=1);

namespace Queue\Swoole\Command;

use Dot\DependencyInjection\Attribute\Inject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function dirname;
use function file;
use function file_exists;
use function is_numeric;
use function json_decode;
use function strtolower;
use function strtotime;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

#[AsCommand(
    name: 'processed',
    description: 'Get successfully processed messages',
)]
class GetProcessedMessagesCommand extends Command
{
    /** @var string $defaultName */
    protected static $defaultName = 'processed';

    #[Inject()]
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setDescription('Get successfully processed messages')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start timestamp (Y-m-d H:i:s)')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End timestamp (Y-m-d H:i:s)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit in days');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $startOption = $input->getOption('start');
            $endOption   = $input->getOption('end');
            $limit       = $input->getOption('limit');

            $startDate = $startOption ? new \DateTimeImmutable($startOption) : null;
            $endDate   = $endOption ? new \DateTimeImmutable($endOption) : null;
        } catch (\Exception $e) {
            $output->writeln('<error>Invalid date format provided.</error>');
            return Command::FAILURE;
        }

        if ($startDate && $startDate->format('H:i:s') === '00:00:00') {
            $startDate = $startDate->setTime(0, 0, 0);
        }

        if ($endDate && $endDate->format('H:i:s') === '00:00:00') {
            $endDate = $endDate->setTime(23, 59, 59);
        }

        if ($limit && is_numeric($limit)) {
            if ($startDate && ! $endDate) {
                $endDate = $startDate->modify("+{$limit} days");
            } elseif (! $startDate && $endDate) {
                $startDate = $endDate->modify("-{$limit} days");
            }
        }

        if (! $endDate) {
            $endDate = new \DateTime();
        }

        if ($startDate > $endDate) {
            $output->writeln('<error>The start date cannot be after the end date.</error>');
            return Command::FAILURE;
        }

        $logPath = dirname(__DIR__, 3) . '/log/queue-log.log';

        if (! file_exists($logPath)) {
            $output->writeln("<error>Log file not found: $logPath</error>");
            return Command::FAILURE;
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $startTimestamp = $startDate?->getTimestamp();
        $endTimestamp   = $endDate->getTimestamp();

        $found = false;

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (! $entry || ! isset($entry['levelName'], $entry['timestamp'])) {
                continue;
            }

            if (strtolower($entry['levelName']) !== 'info') {
                continue;
            }

            $logTimestamp = strtotime($entry['timestamp']);
            if (
                ($startTimestamp && $logTimestamp < $startTimestamp) ||
                ($endTimestamp && $logTimestamp > $endTimestamp)
            ) {
                continue;
            }

            $output->writeln($line);
            $found = true;
        }

        if (! $found) {
            $output->writeln('<comment>No matching log entries found.</comment>');
        }

        return Command::SUCCESS;
    }
}
