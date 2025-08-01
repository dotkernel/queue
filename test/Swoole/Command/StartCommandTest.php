<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\Command\StartCommand;
use Queue\Swoole\PidManager;
use Swoole\Server;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommandTest extends TestCase
{
    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testExecuteWhenServerIsNotRunning(): void
    {
        $input      = $this->createMock(InputInterface::class);
        $output     = $this->createMock(OutputInterface::class);
        $pidManager = $this->createMock(PidManager::class);
        $server     = $this->createMock(Server::class);

        $pidManager->method('read')->willReturn([]);

        $server->master_pid  = 1234;
        $server->manager_pid = 4321;

        $server->expects($this->once())->method('on');
        $server->expects($this->once())->method('start');

        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-server' => [
                    'process-name' => 'test-process',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($pidManager, $server, $config) {
            return match ($id) {
                PidManager::class => $pidManager,
                Server::class => $server,
                'config' => $config,
                default => null,
            };
        });

        $command    = new StartCommand($container);
        $statusCode = $command->run($input, $output);

        $this->assertSame(0, $statusCode);
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testExecuteWhenServerIsAlreadyRunning(): void
    {
        $container  = $this->createMock(ContainerInterface::class);
        $pidManager = $this->createMock(PidManager::class);
        $container->method('get')
            ->with(PidManager::class)
            ->willReturn($pidManager);

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln')
            ->with('<error>Server is already running!</error>');

        $command = $this->getMockBuilder(StartCommand::class)
            ->setConstructorArgs([$container])
            ->onlyMethods(['isRunning'])
            ->getMock();

        $command->method('isRunning')->willReturn(true);

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
    }
}
