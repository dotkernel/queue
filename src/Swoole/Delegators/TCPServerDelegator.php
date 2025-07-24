<?php

declare(strict_types=1);

namespace Queue\Swoole\Delegators;

use Psr\Container\ContainerInterface;
use Queue\App\Message\ExampleMessage;
use Queue\Swoole\Command\GetFailedMessagesCommand;
// Import your commands
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Swoole\Server as TCPSwooleServer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

use function array_merge;
use function array_shift;
use function explode;
use function ltrim;
use function str_starts_with;
use function trim;

class TCPServerDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): TCPSwooleServer
    {
        /** @var TCPSwooleServer $server */
        $server = $callback();

        /** @var MessageBusInterface $bus */
        $bus = $container->get(MessageBusInterface::class);

        $logger = $container->get("dot-log.queue-log");

        $commandMap = [
            'processed' => GetProcessedMessagesCommand::class,
            'failed'    => GetFailedMessagesCommand::class,
        ];

        $server->on('Connect', function ($server, $fd) {
            echo "Client: Connect.\n";
        });

        $server->on('receive', function ($server, $fd, $fromId, $data) use ($logger, $bus, $commandMap, $container) {
            $message  = trim($data);
            $response = '';

            $args        = explode(' ', $message);
            $commandName = array_shift($args);

            if (isset($commandMap[$commandName])) {
                $commandClass    = $commandMap[$commandName];
                $application     = new Application();
                $commandInstance = $container->get($commandClass);
                $application->add($commandInstance);

                $parsedOptions = [];
                foreach ($args as $arg) {
                    if (str_starts_with($arg, '--')) {
                        [$key, $value]           = explode('=', ltrim($arg, '-'), 2) + [null, null];
                        $parsedOptions["--$key"] = $value;
                    }
                }

                $inputData = array_merge(['command' => $commandName], $parsedOptions);
                $input     = new ArrayInput($inputData);
                $output    = new BufferedOutput();

                try {
                    $application->setAutoExit(false);
                    $application->run($input, $output);
                    $response = $output->fetch();
                    $server->send($fd, $response);
                } catch (\Throwable $e) {
                    $logger->error("Error running command: " . $e->getMessage());
                }
            } else {
                $bus->dispatch(new ExampleMessage(["foo" => $data]));
                $bus->dispatch(new ExampleMessage(["foo" => "with 5 seconds delay"]), [
                    new DelayStamp(5000),
                ]);

                $logger->notice("TCP request received", [
                    'fd'      => $fd,
                    'from_id' => $fromId,
                    'data'    => $data,
                ]);
            }
        });

        $server->on('Close', function ($server, $fd) {
            echo "Client: Close.\n";
        });

        return $server;
    }
}
