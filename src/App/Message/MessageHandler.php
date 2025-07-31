<?php

declare(strict_types=1);

namespace Queue\App\Message;

use Dot\DependencyInjection\Attribute\Inject;
use Dot\Log\Logger;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MessageHandler
{
    #[Inject(
        MessageBusInterface::class,
        'dot-log.queue-log',
        'config',
    )]
    public function __construct(
        protected MessageBusInterface $bus,
        protected Logger $logger,
        protected array $config,
    ) {
    }

    public function __invoke(Message $message): void
    {
        $payload = $message->getPayload();

        try {
            // Throwing an exception to satisfy PHPStan (replace with own code)
            // For proof of concept and testing purposes message "control" will automatically mark it as successfully
            // processed and logged as info
            if ($payload['foo'] === 'control') {
                $this->logger->info($payload['foo'] . ': was processed successfully');
            } else {
                throw new \Exception("Failed to execute");
            }
        } catch (\Throwable $exception) {
            $this->logger->error($payload['foo'] . ' failed with message: '
                . $exception->getMessage() . ' after ' . ($payload['retry'] ?? 0) . ' retries');
            $this->retry($payload);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function retry(array $payload): void
    {
        if (! isset($payload['retry'])) {
            $this->bus->dispatch(new Message(["foo" => $payload['foo'], 'retry' => 1]), [
                new DelayStamp($this->config['fail-safe']['first_retry']),
            ]);
        } else {
            $retry = $payload['retry'];
            switch ($retry) {
                case 1:
                    $delay = $this->config['fail-safe']['second_retry'];
                    $this->bus->dispatch(new Message(["foo" => $payload['foo'], 'retry' => ++$retry]), [
                        new DelayStamp($delay),
                    ]);
                    break;
                case 2:
                    $delay = $this->config['fail-safe']['third_retry'];
                    $this->bus->dispatch(new Message(["foo" => $payload['foo'], 'retry' => ++$retry]), [
                        new DelayStamp($delay),
                    ]);
                    break;
            }
        }
    }
}
