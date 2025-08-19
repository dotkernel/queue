<?php

declare(strict_types=1);

namespace QueueTest\Swoole;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\ServerFactory;
use Swoole\Server;

use const SWOOLE_BASE;
use const SWOOLE_SOCK_TCP;

class ServerFactoryTest extends TestCase
{
    private ServerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ServerFactory();
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    public function testInvokeWithMinimalValidConfig(): void
    {
        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-tcp-server' => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $server = $this->factory->__invoke($container);

        $this->assertContainsOnlyInstancesOf(Server::class, [$server]);
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    public function testInvokeWithCustomValidConfig(): void
    {
        $config = [
            'dotkernel-queue-swoole' => [
                'enable_coroutine'  => true,
                'swoole-tcp-server' => [
                    'host'     => '127.0.0.1',
                    'port'     => 9502,
                    'mode'     => SWOOLE_BASE,
                    'protocol' => SWOOLE_SOCK_TCP,
                    'options'  => [
                        'worker_num' => 1,
                    ],
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $server = $this->factory->__invoke($container);

        $this->assertContainsOnlyInstancesOf(Server::class, [$server]);
    }

    /**
     * @throws Exception
     */
    public function testThrowsOnInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port');

        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-tcp-server' => [
                    'port' => 70000,
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $this->factory->__invoke($container);
    }

    /**
     * @throws Exception
     */
    public function testThrowsOnInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid server mode');

        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-tcp-server' => [
                    'mode' => -1,
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $this->factory->__invoke($container);
    }

    /**
     * @throws Exception
     */
    public function testThrowsOnInvalidProtocol(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid server protocol');

        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-tcp-server' => [
                    'protocol' => -99,
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $this->factory->__invoke($container);
    }
}
