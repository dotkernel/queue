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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

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

    /**
     * @throws Exception
     */
    public function testInvokeSuccessfulProcessing(): void
    {
        $payload = ['foo' => 'control'];
        $message = $this->createMock(Message::class);
        $message->method('getPayload')->willReturn($payload);

        $this->handler->__invoke($message);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws Exception
     */
    public function testInvokeFailureTriggersFirstRetry(): void
    {
        $payload = ['foo' => 'fail'];
        $message = $this->createMock(Message::class);
        $message->method('getPayload')->willReturn($payload);

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($msg) {
                    return $msg instanceof Message
                        && $msg->getPayload()['foo'] === 'fail'
                        && $msg->getPayload()['retry'] === 1;
                }),
                $this->callback(function ($stamps) {
                    return isset($stamps[0]) && $stamps[0] instanceof DelayStamp
                        && $stamps[0]->getDelay() === 1000;
                })
            )
            ->willReturn(new Envelope($message));

        $this->handler->__invoke($message);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRetrySecondTime(): void
    {
        $payload = ['foo' => 'retry_test', 'retry' => 1];

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($msg) {
                    return $msg instanceof Message
                        && $msg->getPayload()['retry'] === 2
                        && $msg->getPayload()['foo'] === 'retry_test';
                }),
                $this->callback(function ($stamps) {
                    return isset($stamps[0]) && $stamps[0] instanceof DelayStamp
                        && $stamps[0]->getDelay() === 2000;
                })
            )
            ->willReturn(new Envelope(new Message($payload)));

        $this->handler->retry($payload);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRetryThirdTime(): void
    {
        $payload = ['foo' => 'retry_test', 'retry' => 2];

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($msg) {
                    return $msg instanceof Message
                        && $msg->getPayload()['retry'] === 3
                        && $msg->getPayload()['foo'] === 'retry_test';
                }),
                $this->callback(function ($stamps) {
                    return isset($stamps[0]) && $stamps[0] instanceof DelayStamp
                        && $stamps[0]->getDelay() === 3000;
                })
            )
            ->willReturn(new Envelope(new Message($payload)));

        $this->handler->retry($payload);
    }
}
