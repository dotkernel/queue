<?php

declare(strict_types=1);

namespace Queue\Swoole\Delegators;

use Psr\Container\ContainerInterface;
use Queue\App\Message\Message;
use Swoole\Server as TCPSwooleServer;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TCPServerDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): TCPSwooleServer
    {
        /** @var TCPSwooleServer $server */
        $server = $callback();

        /** @var MessageBusInterface $bus */
        $bus = $container->get(MessageBusInterface::class);

        $logger = $container->get("dot-log.queue-log");

        $server->on('Connect', function ($server, $fd) {
            echo "Client: Connect.\n";
        });

        // Register the function for the event `receive`
        $server->on('receive', function ($server, $fd, $fromId, $data) use ($logger, $bus) {
            $bus->dispatch(new Message(["foo" => $data]));
            $bus->dispatch(new Message(["foo" => "with 5 seconds delay"]), [
                new DelayStamp(5000),
            ]);

            $server->send($fd, "Server: {$data}");
            $logger->notice("Request received on receive", [
                'fd'      => $fd,
                'from_id' => $fromId,
            ]);
        });

        // Listen for the 'Close' event.
        $server->on('Close', function ($server, $fd) {
            echo "Client: Close.\n";
        });

        return $server;
    }
}
