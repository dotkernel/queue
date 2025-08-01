<?php

declare(strict_types=1);

namespace QueueTest\Swoole;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Queue\Swoole\PidManagerFactory;
use ReflectionException;

final class PidManagerFactoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testCreatesPidManagerWithConfiguredPidFile(): void
    {
        $expectedPath = '/tmp/custom-pid-file.pid';

        $config = [
            'dotkernel-queue-swoole' => [
                'swoole-tcp-server' => [
                    'options' => [
                        'pid_file' => $expectedPath,
                    ],
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn($config);

        $factory    = new PidManagerFactory();
        $pidManager = $factory($container);

        $pidFilePath = $this->getPrivateProperty($pidManager);
        $this->assertSame($expectedPath, $pidFilePath);
    }

    /**
     * @throws ReflectionException
     */
    private function getPrivateProperty(object $object): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property   = $reflection->getProperty('pidFile');
        return $property->getValue($object);
    }
}
