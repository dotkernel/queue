<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command\Factory;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\Command\Factory\StartCommandFactory;
use Queue\Swoole\Command\StartCommand;

class StartCommandFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFactoryReturnsStartCommandInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new StartCommandFactory();
        $command = $factory($container);

        $this->assertContainsOnlyInstancesOf(StartCommand::class, [$command]);
    }
}
