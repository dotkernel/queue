<?php

declare(strict_types=1);

namespace Queue\App\Message;

use Dot\DependencyInjection\Attribute\Inject;
use Dot\Log\Logger;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function __invoke(Message $message): void
    {
        $payload = $message->getPayload();

        try {
            if ($payload['foo'] === 'control') {
                //user control message to log successfully processed message
                $this->logger->info($payload['foo'] . ' processed successfully');
            } elseif ($payload['foo'] === 'retry') {
                //user retry message to test retry functionality
                throw new \RuntimeException("Intentional failure for testing retries");
            }
        } catch (\Throwable $e) {
            $retryCount = $payload['retry_count'] ?? 0;

            if ($retryCount === 0) {
                $this->logger->error(
                    "Message '{$payload['foo']}' failed because: " . $e->getMessage()
                );
            } else {
                $this->logger->error(
                    "Message '{$payload['foo']}' failed because: " . $e->getMessage() . " Retry {$retryCount}"
                );
            }

            $payload['retry_count'] = $retryCount + 1;
            $message->setPayload($payload);

            throw $e;
        }
    }
}
