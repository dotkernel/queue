<?php

declare(strict_types=1);

namespace Queue\Swoole\Command;

use Dot\DependencyInjection\Attribute\Inject;
use Redis;
use RedisException;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

use function count;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function method_exists;
use function preg_match;
use function str_repeat;
use function stripcslashes;
use function unserialize;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
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
        $this->setDescription('Get all queued messages from Redis stream "messages"')
            ->addOption('stream', null, InputOption::VALUE_REQUIRED, 'stream name');
    }

    /**
     * @throws RedisException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $streamName = (string) $input->getOption('stream');
        $entries    = $this->redis->xRange($streamName, '-', '+');

        if (empty($entries)) {
            $output->writeln("<info>No messages queued found in Redis stream $streamName</info>");
            return Command::SUCCESS;
        }

        foreach ($entries as $id => $entry) {
            $output->writeln("<comment>Message ID:</comment> $id");

            foreach ($entry as $field => $value) {
                $raw = is_string($value) ? $value : (string) $value;

                if (preg_match('/^s:\d+:\".*\";$/s', $raw)) {
                    $raw = @unserialize($raw, ['allowed_classes' => true]);
                }

                $json = json_decode((string) $raw, true);

                if (is_array($json) && isset($json['body'])) {
                    $body     = stripcslashes($json['body']);
                    $envelope = @unserialize($body, ['allowed_classes' => true]);

                    if ($envelope instanceof Envelope) {
                        $message = $envelope->getMessage();
                        $output->writeln("  <info>$field</info>:");
                        $output->writeln("    <comment>Message Class</comment>: " . $message::class);

                        $payload = null;
                        if (method_exists($message, 'getPayload')) {
                            $payload = $message->getPayload();
                        } else {
                            try {
                                $refClass = new ReflectionClass($message);
                                if ($refClass->hasProperty('payload')) {
                                    $prop    = $refClass->getProperty('payload');
                                    $payload = $prop->getValue($message);
                                }
                            } catch (\Throwable $e) {
                                $payload = '[unavailable: ' . $e->getMessage() . ']';
                            }
                        }

                        if ($payload !== null) {
                            $output->writeln("    <comment>Payload</comment>:");
                            $output->writeln(
                                json_encode(
                                    $payload,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                )
                            );
                        }

                        $redeliveryStamps = $envelope->all(RedeliveryStamp::class);
                        if (! empty($redeliveryStamps)) {
                            $output->writeln("    <comment>Timestamps</comment>:");
                            foreach ($redeliveryStamps as $stamp) {
                                if ($stamp instanceof RedeliveryStamp) {
                                    $output->writeln("      - "
                                        . $stamp->getRedeliveredAt()->format('Y-m-d H:i:s')
                                        . " (retry count: " . $stamp->getRetryCount() . ")");
                                }
                            }
                        }
                    } else {
                        $output->writeln("  <info>$field</info>: failed to unserialize envelope");
                    }
                } else {
                    $output->writeln("  <info>$field</info>: $raw");
                }
            }

            $output->writeln(str_repeat('-', 40));
        }

        $total = count($entries);
        $output->writeln("<info>Total queued messages in stream $streamName:</info> $total");
        $output->writeln(str_repeat('-', 40));

        return Command::SUCCESS;
    }
}
