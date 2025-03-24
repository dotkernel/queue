<?php

namespace Queue\App\Message;

use Dot\Log\Logger;
use Psr\Container\ContainerInterface;
class ExampleMessageHandler
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function __invoke(ExampleMessage $message)
    {

        /** @var Logger $logger */
        $logger = $this->container->get("dot-log.queue-log");

        $logger->info("message: " . $message->getPayload()['foo'] ?? null);
    }
}