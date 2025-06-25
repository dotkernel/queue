<?php

declare(strict_types=1);

namespace Queue\Swoole;

use Psr\Container\ContainerInterface;

use function sys_get_temp_dir;

class PidManagerFactory
{
    public function __invoke(ContainerInterface $container): PidManager
    {
        $config = $container->get('config');
        return new PidManager(
            $config['dotkernel-queue-swoole']['swoole-tcp-server']['options']['pid_file']
            ?? sys_get_temp_dir() . '/dotkernel-queue-swoole.pid'
        );
    }
}
