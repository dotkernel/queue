<?php

declare(strict_types=1);

namespace Queue\App\Message;

use Psr\Container\ContainerInterface;

class ExampleMessageHandlerFactory
{
    public function __invoke(ContainerInterface $container): ExampleMessageHandler
    {
        return new ExampleMessageHandler($container);
    }
}
