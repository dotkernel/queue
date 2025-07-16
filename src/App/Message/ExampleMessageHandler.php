<?php

declare(strict_types=1);

namespace Queue\App\Message;

use Dot\DependencyInjection\Attribute\Inject;
use Dot\Log\Logger;

class ExampleMessageHandler
{
    #[Inject(
        'dot-log.queue-log',
        'config',
    )]
    public function __construct(
        protected Logger $logger,
        protected array $config,
    ) {
    }

    public function __invoke(ExampleMessage $message): void
    {
        $this->logger->info("message: " . $message->getPayload()['foo']);
    }
}
