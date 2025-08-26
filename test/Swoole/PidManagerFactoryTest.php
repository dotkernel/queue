<?php

declare(strict_types=1);

namespace QueueTest\Swoole;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Queue\Swoole\PidManagerFactory;
use ReflectionClass;
use ReflectionException;

final class PidManagerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
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
        $reflection = new ReflectionClass($object);
        $property   = $reflection->getProperty('pidFile');
        return $property->getValue($object);
    }
}
