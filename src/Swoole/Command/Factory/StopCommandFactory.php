<?php

declare(strict_types=1);

namespace Queue\Swoole\Command\Factory;

use Psr\Container\ContainerInterface;
use Queue\Swoole\Command\StopCommand;
use Queue\Swoole\PidManager;

class StopCommandFactory
{
    public function __invoke(ContainerInterface $container): StopCommand
    {
        return new StopCommand($container->get(PidManager::class));
    }
}
