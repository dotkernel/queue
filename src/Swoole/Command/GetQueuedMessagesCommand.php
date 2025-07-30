<?php

declare(strict_types=1);

namespace Queue\Swoole\Command;

use Dot\DependencyInjection\Attribute\Inject;
use Redis;
use RedisException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function json_encode;
use function str_repeat;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(
    name: 'inventory',
    description: 'Get all queued messages from Redis stream "messages"',
)]
class GetQueuedMessagesCommand extends Command
{
    protected static string $defaultName = 'inventory';

    private Redis $redis;

    #[Inject('redis')]
    public function __construct(Redis $redis)
    {
        parent::__construct(self::$defaultName);
        $this->redis = $redis;
    }

    protected function configure(): void
    {
        $this->setDescription('Get all queued messages from Redis stream "messages"');
    }

    /**
     * @throws RedisException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entries = $this->redis->xRange('messages', '-', '+');

        if (empty($entries)) {
            $output->writeln('<info>No messages queued found in Redis stream "messages".</info>');
            return Command::SUCCESS;
        }

        foreach ($entries as $id => $entry) {
            $output->writeln("<info>Message ID:</info> $id");
            $output->writeln(json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $output->writeln(str_repeat('-', 40));
        }

        $total = count($entries);
        $output->writeln("<info>Total queued messages in stream 'messages':</info> $total");
        $output->writeln(str_repeat('-', 40));

        return Command::SUCCESS;
    }
}
