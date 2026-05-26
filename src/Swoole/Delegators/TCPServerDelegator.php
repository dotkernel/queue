<?php

declare(strict_types=1);

namespace Queue\Swoole\Delegators;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Queue\App\Message\Message;
use Queue\Swoole\Command\GetFailedMessagesCommand;
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Queue\Swoole\Command\GetQueuedMessagesCommand;
use Queue\Swoole\Exception\RuntimeException;
use Swoole\Server as TCPSwooleServer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;

use function array_merge;
use function array_shift;
use function explode;
use function ltrim;
use function method_exists;
use function sprintf;
use function str_starts_with;
use function trim;

use const PHP_EOL;

class TCPServerDelegator
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): TCPSwooleServer
    {
        /** @var TCPSwooleServer $server */
        $server = $callback();

        /** @var MessageBusInterface $bus */
        $bus = $container->get(MessageBusInterface::class);

        $logger = $container->get('dot-log.queue-log');

        $server->on('connect', function (TCPSwooleServer $server, int $fd) {
            echo 'Client: Connect.' . PHP_EOL;
        });

        $server->on('receive', function ($server, $fd, $fromId, $data) use ($logger, $bus, $container) {
            $commandMap = [
                'processed' => GetProcessedMessagesCommand::class,
                'failed'    => GetFailedMessagesCommand::class,
                'inventory' => GetQueuedMessagesCommand::class,
            ];

            $message     = trim($data);
            $args        = explode(' ', $message);
            $commandName = array_shift($args);

            if (isset($commandMap[$commandName])) {
                $commandClass    = $commandMap[$commandName];
                $application     = new Application();
                $commandInstance = $container->get($commandClass);
                if (method_exists($application, 'addCommand')) {
                    $application->addCommand($commandInstance);
                } elseif (method_exists($application, 'add')) {
                    $application->add($commandInstance);
                } else {
                    throw new RuntimeException(
                        sprintf('%s contains no "add" or "addCommand" method.', $application::class)
                    );
                }

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
                } catch (Throwable $e) {
                    $logger->error('Error running command: ' . $e->getMessage());
                }
            } else {
                $bus->dispatch(new Message(['foo' => $message]));
                $bus->dispatch(new Message(['foo' => 'with 5 seconds delay']), [
                    new DelayStamp(5000),
                ]);

                $logger->notice('TCP request received', [
                    'fd'      => $fd,
                    'from_id' => $fromId,
                    'data'    => $data,
                ]);
            }
        });

        $server->on('close', function (TCPSwooleServer $server, int $fd) {
            echo 'Client: Close.' . PHP_EOL;
        });

        return $server;
    }
}
