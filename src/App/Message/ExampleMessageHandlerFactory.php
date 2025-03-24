<?php

namespace Queue\App\Message;

use Psr\Container\ContainerInterface;

class ExampleMessageHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new ExampleMessageHandler($container);
    }

}