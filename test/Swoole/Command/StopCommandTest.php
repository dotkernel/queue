<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Queue\Swoole\Command\StopCommand;
use Queue\Swoole\PidManager;
use Symfony\Component\Console\Tester\CommandTester;

class StopCommandTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testExecuteWhenServerIsNotRunning(): void
    {
        if (! \extension_loaded('swoole')) {
            $this->markTestSkipped('Swoole extension not loaded.');
        }

        $pidManager = $this->createMock(PidManager::class);

        $command = $this->getMockBuilder(StopCommand::class)
            ->setConstructorArgs([$pidManager])
            ->onlyMethods(['isRunning'])
            ->getMock();

        $command->method('isRunning')->willReturn(false);

        $tester   = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Server is not running', $tester->getDisplay());
    }

    /**
     * @throws Exception
     */
    public function testExecuteWhenServerStopsSuccessfully(): void
    {
        if (! \extension_loaded('swoole')) {
            $this->markTestSkipped('Swoole extension not loaded.');
        }

        $pidManager = $this->createMock(PidManager::class);
        $pidManager->method('read')->willReturn(['1234']);
        $pidManager->expects($this->once())->method('delete');

        $command = $this->getMockBuilder(StopCommand::class)
            ->setConstructorArgs([$pidManager])
            ->onlyMethods(['isRunning'])
            ->getMock();

        $command->method('isRunning')->willReturn(true);

        $command->killProcess = function (int $pid, ?int $signal = null): bool {
            return true;
        };

        $tester   = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Server stopped', $tester->getDisplay());
    }

    /**
     * @throws Exception
     */
    public function testExecuteWhenServerFailsToStop(): void
    {
        if (! \extension_loaded('swoole')) {
            $this->markTestSkipped('Swoole extension not loaded.');
        }

        $pidManager = $this->createMock(PidManager::class);
        $pidManager->method('read')->willReturn(['1234']);
        $pidManager->expects($this->never())->method('delete');

        $command = $this->getMockBuilder(StopCommand::class)
            ->setConstructorArgs([$pidManager])
            ->onlyMethods(['isRunning'])
            ->getMock();

        $command->method('isRunning')->willReturn(true);
        $command->waitThreshold = 1;

        $command->killProcess = function (int $pid, ?int $signal = null): bool {
            return $signal === 0;
        };

        $tester   = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Error stopping server', $tester->getDisplay());
    }
}
