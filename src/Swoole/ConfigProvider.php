<?php

declare(strict_types=1);

namespace Queue\Swoole;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use Queue\Swoole\Command\Factory\StartCommandFactory;
use Queue\Swoole\Command\Factory\StopCommandFactory;
use Queue\Swoole\Command\GetFailedMessagesCommand;
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Queue\Swoole\Command\StartCommand;
use Queue\Swoole\Command\StopCommand;
use Queue\Swoole\Delegators\TCPServerDelegator;
use Swoole\Server as TCPSwooleServer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            "delegators" => [
                TCPSwooleServer::class => [TCPServerDelegator::class],
            ],
            "factories"  => [
                TCPSwooleServer::class             => ServerFactory::class,
                PidManager::class                  => PidManagerFactory::class,
                StartCommand::class                => StartCommandFactory::class,
                StopCommand::class                 => StopCommandFactory::class,
                GetProcessedMessagesCommand::class => AttributedServiceFactory::class,
                GetFailedMessagesCommand::class    => AttributedServiceFactory::class,
            ],
            "aliases"    => [],
        ];
    }
}
