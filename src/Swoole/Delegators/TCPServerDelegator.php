<?php

namespace Queue\Swoole\Delegators;

use Psr\Container\ContainerInterface;

use Swoole\Server as TCPSwooleServer;

class TCPServerDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback)
    {
        /** @var TCPSwooleServer $server */
        $server = $callback();

        $logger = $container->get("dot-log.queue-log");

        $server->on('Connect', function ($server, $fd) {
            echo "Client: Connect.\n";
        });

        // Register the function for the event `receive`
        $server->on('receive', function ($server, $fd, $from_id, $data) use ($logger) {
            $server->send($fd, "Server: {$data}");
            $logger->notice("Request received on receive", [
                'fd' => $fd,
                'from_id' => $from_id
            ]);
        });

        // Listen for the 'Close' event.
        $server->on('Close', function ($server, $fd) {
            echo "Client: Close.\n";
        });

        return $server;

    }

}