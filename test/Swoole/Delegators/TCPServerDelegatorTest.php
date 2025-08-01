<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Delegators;

use Dot\Log\Logger;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Queue\App\Message\Message;
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Queue\Swoole\Delegators\TCPServerDelegator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class TCPServerDelegatorTest extends TestCase
{
    private Logger $logger;
    private MessageBusInterface|MockObject $bus;
    private ContainerInterface|MockObject $container;
    private DummySwooleServer $server;

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->logger = new Logger([
            'writers' => [
                'FileWriter' => [
                    'name'  => 'null',
                    'level' => Logger::ALERT,
                ],
            ],
        ]);

        $this->bus       = $this->createMock(MessageBusInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->server    = new DummySwooleServer();
    }

    public function testCallbacksAreRegistered(): void
    {
        $callback = fn() => $this->server;

        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
        ]);

        $delegator = new TCPServerDelegator();
        $result    = $delegator($this->container, 'tcp-server', $callback);

        $this->assertSame($this->server, $result);
        $this->assertArrayHasKey('Connect', $this->server->callbacks);
        $this->assertArrayHasKey('receive', $this->server->callbacks);
        $this->assertArrayHasKey('Close', $this->server->callbacks);

        foreach (['Connect', 'receive', 'Close'] as $event) {
            $this->assertIsCallable($this->server->callbacks[$event]);
        }
    }

    public function testConnectOutputsExpectedString(): void
    {
        $callback = fn() => $this->server;

        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
        ]);

        $delegator = new TCPServerDelegator();
        $delegator($this->container, 'tcp-server', $callback);

        $this->expectOutputString("Client: Connect.\n");

        $connectCb = $this->server->callbacks['Connect'];
        $connectCb($this->server, 1);
    }

    public function testCloseOutputsExpectedString(): void
    {
        $callback = fn() => $this->server;

        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
        ]);

        $delegator = new TCPServerDelegator();
        $delegator($this->container, 'tcp-server', $callback);

        $this->expectOutputString("Client: Close.\n");

        $closeCb = $this->server->callbacks['Close'];
        $closeCb($this->server, 1);
    }

    public function testReceiveDispatchesMessagesAndLogsWhenUnknownCommand(): void
    {
        $callback = fn() => $this->server;

        $this->bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($message) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(Message::class, $message);
                    $this->assertEquals('hello', $message->getPayload()['foo']);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(Message::class, $message);
                    $this->assertEquals('with 5 seconds delay', $message->getPayload()['foo']);
                } else {
                    $this->fail('dispatch called more than twice');
                }

                return new Envelope($message);
            });

        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
        ]);

        $delegator = new TCPServerDelegator();
        $delegator($this->container, 'tcp-server', $callback);

        $receiveCb = $this->server->callbacks['receive'];

        $receiveCb($this->server, 42, 5, "hello");
    }

    public function testReceiveExecutesKnownCommandSuccessfully(): void
    {
        $callback = fn() => $this->server;

        $commandMock = $this->getMockBuilder(GetProcessedMessagesCommand::class)
            ->onlyMethods(['execute'])
            ->getMock();

        $commandMock->method('execute')->willReturnCallback(function ($input, $output) {
            $output->writeln('processed output text');
            return 0;
        });

        $this->server = new class extends DummySwooleServer {
            public ?string $sentData = null;

            /**
             * @param int $fd
             * @param string $data
             * @param int $serverSocket
             */
            public function send($fd, $data, $serverSocket = -1): bool
            {
                $this->sentData = $data;
                return true;
            }
        };

        // Make sure the container returns our mock command instance
        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
            [GetProcessedMessagesCommand::class, $commandMock],
        ]);

        $delegator = new TCPServerDelegator();
        $delegator($this->container, 'tcp-server', $callback);

        $receiveCb = $this->server->callbacks['receive'];

        $receiveCb($this->server, 1, 1, "processed");

        $this->assertNotNull($this->server->sentData);
        $this->assertStringContainsString('processed output text', $this->server->sentData);
    }

    public function testReceiveParsesKnownOptions(): void
    {
        $callback = fn() => $this->server;

        $sentData     = null;
        $this->server = new class extends DummySwooleServer {
            public ?string $sentData = null;

            /**
             * @param int $fd
             * @param string $data
             * @param int $serverSocket
             */
            public function send($fd, $data, $serverSocket = -1): bool
            {
                $this->sentData = $data;
                return true;
            }
        };

        $commandMock = $this->getMockBuilder(GetProcessedMessagesCommand::class)
            ->onlyMethods(['execute'])
            ->getMock();

        $commandMock->method('execute')->willReturnCallback(function ($input, $output) {
            $output->writeln('processed output text with known options');
            return 0;
        });

        $this->container->method('get')->willReturnMap([
            [MessageBusInterface::class, $this->bus],
            ['dot-log.queue-log', $this->logger],
            [GetProcessedMessagesCommand::class, $commandMock],
        ]);

        $delegator = new TCPServerDelegator();
        $delegator($this->container, 'tcp-server', $callback);

        $receiveCb = $this->server->callbacks['receive'];

        $receiveCb($this->server, 1, 1, "processed --start=1 --end=5");

        $this->assertNotNull($this->server->sentData);
        $this->assertStringContainsString('processed output text with known options', $this->server->sentData);
    }
}
