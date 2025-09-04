<?php

declare(strict_types=1);

namespace QueueTest\App\Message;

use Dot\Log\Logger;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Queue\App\Message\Message;
use Queue\App\Message\MessageHandler;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageHandlerTest extends TestCase
{
    private MessageBusInterface|MockObject $bus;
    private Logger $logger;
    private array $config;
    private MessageHandler $handler;

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    protected function setUp(): void
    {
        $this->bus    = $this->createMock(MessageBusInterface::class);
        $this->logger = new Logger([
            'writers' => [
                'FileWriter' => [
                    'name'  => 'null',
                    'level' => Logger::ALERT,
                ],
            ],
        ]);
        $this->config = [
            'fail-safe'    => [
                'first_retry'  => 1000,
                'second_retry' => 2000,
                'third_retry'  => 3000,
            ],
            'notification' => [
                'server' => [
                    'protocol' => 'tcp',
                    'host'     => 'localhost',
                    'port'     => '8556',
                    'eof'      => "\n",
                ],
            ],
            'application'  => [
                'name' => 'dotkernel',
            ],
        ];

        $this->handler = new MessageHandler($this->bus, $this->logger, $this->config);
    }

    public function testControlMessageDoesNotThrowAndDoesNotSetRetryCount(): void
    {
        $handler = $this->handler;

        $message = new Message(['foo' => 'control']);
        $handler($message);

        $payload = $message->getPayload();
        $this->assertArrayNotHasKey('retry_count', $payload);
    }

    public function testRetryMessageThrowsExceptionAndSetsRetryCount(): void
    {
        $handler = $this->handler;

        $message = new Message(['foo' => 'retry']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Intentional failure for testing retries");

        try {
            $handler($message);
        } finally {
            $payload = $message->getPayload();
            $this->assertArrayHasKey('retry_count', $payload);
            $this->assertEquals(1, $payload['retry_count']); // first retry
        }
    }

    public function testRetryMessageWithExistingRetryCountIncrementsIt(): void
    {
        $handler = $this->handler;

        $message = new Message([
            'foo'         => 'retry',
            'retry_count' => 2,
        ]);

        $this->expectException(RuntimeException::class);

        try {
            $handler($message);
        } finally {
            $payload = $message->getPayload();
            $this->assertEquals(3, $payload['retry_count']); // incremented from 2 → 3
        }
    }
}
