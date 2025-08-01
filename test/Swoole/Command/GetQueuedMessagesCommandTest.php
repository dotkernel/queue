<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Queue\Swoole\Command\GetQueuedMessagesCommand;
use Redis;
use RedisException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function array_keys;
use function count;

class GetQueuedMessagesCommandTest extends TestCase
{
    private Redis|MockObject $redisMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
    }

    public function testExecuteWithNoMessages(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('xRange')
            ->with('messages', '-', '+')
            ->willReturn([]);

        $command = new GetQueuedMessagesCommand($this->redisMock);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode   = $command->run($input, $output);
        $outputText = $output->fetch();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No messages queued found', $outputText);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testExecuteWithMessages(): void
    {
        $fakeMessages = [
            '1691000000000-0' => ['type' => 'testEmail', 'payload' => '{"to":"test@dotkernel.com"}'],
            '1691000000001-0' => ['type' => 'testSms', 'payload' => '{"to":"+123456789"}'],
        ];

        $this->redisMock
            ->expects($this->once())
            ->method('xRange')
            ->with('messages', '-', '+')
            ->willReturn($fakeMessages);

        $command = new GetQueuedMessagesCommand($this->redisMock);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode   = $command->run($input, $output);
        $outputText = $output->fetch();

        $this->assertEquals(Command::SUCCESS, $exitCode);

        foreach (array_keys($fakeMessages) as $id) {
            $this->assertStringContainsString("Message ID:", $outputText);
            $this->assertStringContainsString($id, $outputText);
        }

        $this->assertStringContainsString('Total queued messages in stream', $outputText);
        $this->assertStringContainsString((string) count($fakeMessages), $outputText);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRedisThrowsException(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('xRange')
            ->willThrowException(new RedisException("Redis unavailable"));

        $command = new GetQueuedMessagesCommand($this->redisMock);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $this->expectException(RedisException::class);
        $command->run($input, $output);
    }
}
