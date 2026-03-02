<?php

declare(strict_types=1);

namespace Queue\Swoole\Command\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Queue\Swoole\Command\StopCommand;
use Queue\Swoole\PidManager;

class StopCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): StopCommand
    {
        return new StopCommand($container->get(PidManager::class));
    }
}
