<?php

declare(strict_types=1);

namespace Queue\Swoole\Command;

use Dot\DependencyInjection\Attribute\Inject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function date;
use function file;
use function is_numeric;
use function json_decode;
use function preg_match;
use function strtolower;
use function strtotime;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

#[AsCommand(
    name: 'failed',
    description: 'Get processing failure messages.',
)]
class GetFailedMessagesCommand extends Command
{
    protected static string $defaultName = 'failed';

    #[Inject()]
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setDescription('Get processing failure messages.')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start timestamp (Y-m-d H:i:s)')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End timestamp (Y-m-d H:i:s)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit in days');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = $input->getOption('start');
        $end   = $input->getOption('end');
        $limit = $input->getOption('limit');

        if (! $end) {
            $end = date('Y-m-d H:i:s');
        } elseif (! preg_match('/\d{2}:\d{2}:\d{2}/', $end)) {
            $end .= ' 23:59:59';
        }

        if ($limit && is_numeric($limit) && ! $start) {
            $start = date('Y-m-d H:i:s', strtotime("-{$limit} days", strtotime($end)));
        } elseif ($start && ! preg_match('/\d{2}:\d{2}:\d{2}/', $start)) {
            $start .= ' 00:00:00';
        }

        $startTimestamp = $start ? strtotime($start) : null;
        $endTimestamp   = $end ? strtotime($end) : null;

        $logPath = 'log/queue-log.log';
        $lines   = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (! $entry || ! isset($entry['levelName'], $entry['timestamp'])) {
                continue;
            }

            if (strtolower($entry['levelName']) !== 'error') {
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
        }

        return Command::SUCCESS;
    }
}
