<?php

declare(strict_types=1);

namespace Queue\Swoole\Command\Factory;

use Psr\Container\ContainerInterface;
use Queue\Swoole\Command\StartCommand;

class StartCommandFactory
{
    public function __invoke(ContainerInterface $container): StartCommand
    {
        return new StartCommand($container);
    }
}
