<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Delegators;

use Dot\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\Delegators\TCPServerDelegator;
use Swoole\Server;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class TCPServerDelegatorTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    public function testInvokeRegistersAllCallbacks(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $bus    = $this->createMock(MessageBusInterface::class);

        $server   = new DummySwooleServer();
        $callback = fn (): Server => $server;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [MessageBusInterface::class, $bus],
            ['dot-log.queue-log', $logger],
        ]);

        $delegator = new TCPServerDelegator();
        $result    = $delegator($container, 'tcp-server', $callback);

        $this->assertContainsOnlyInstancesOf(Server::class, [$result]);
        $this->assertArrayHasKey('Connect', $server->callbacks);
        $this->assertArrayHasKey('receive', $server->callbacks);
        $this->assertArrayHasKey('Close', $server->callbacks);

        foreach (['Connect', 'receive', 'Close'] as $event) {
            $this->assertIsCallable($server->callbacks[$event]);
        }
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    public function testReceiveCallbackDispatchesMessagesAndLogs(): void
    {
        $dispatched = [];

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function ($message) use (&$dispatched) {
            $dispatched[] = $message;
            return new Envelope($message);
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with(
                $this->equalTo("Request received on receive"),
                $this->arrayHasKey('fd')
            );

        $server   = new DummySwooleServer();
        $callback = fn (): Server => $server;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [MessageBusInterface::class, $bus],
            ['dot-log.queue-log', $logger],
        ]);

        $delegator = new TCPServerDelegator();
        $result    = $delegator($container, 'tcp-server', $callback);

        $this->assertContainsOnlyInstancesOf(Server::class, [$result]);

        $receive = $server->callbacks['receive'] ?? null;
        $this->assertIsCallable($receive);

        $receive($server, 1, 1, 'hello');

        $this->assertCount(2, $dispatched);
    }
}
