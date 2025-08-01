<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command\Factory;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\Command\Factory\StopCommandFactory;
use Queue\Swoole\Command\StopCommand;
use Queue\Swoole\PidManager;

class StopCommandFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFactoryReturnsStopCommandInstance(): void
    {
        $pidManager = $this->createMock(PidManager::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(PidManager::class)
            ->willReturn($pidManager);

        $factory = new StopCommandFactory();

        $command = $factory($container);

        $this->assertContainsOnlyInstancesOf(StopCommand::class, [$command]);
    }
}
