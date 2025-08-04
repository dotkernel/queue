<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Queue\Swoole\Command\GetFailedMessagesCommand;
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function unlink;

use const PHP_EOL;

class GetDataFromLogsCommandTest extends TestCase
{
    private string $logDir;
    private string $logPath;

    protected function setUp(): void
    {
        $this->logDir  = dirname(__DIR__, 3) . '/log';
        $this->logPath = $this->logDir . '/queue-log.log';
        if (! is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
        file_put_contents($this->logPath, '');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * @dataProvider commandProvider
     */
    public function testInvalidDateFormat(string $commandClass): void
    {
        $command = new $commandClass();
        $input   = new ArrayInput(['--start' => 'not-a-date']);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $exit);
        $this->assertStringContainsString('Invalid date format', $output->fetch());
    }

    /**
     * @dataProvider commandProvider
     */
    public function testStartAfterEnd(string $commandClass): void
    {
        $command = new $commandClass();
        $input   = new ArrayInput([
            '--start' => '2024-01-02 00:00:00',
            '--end'   => '2024-01-01 00:00:00',
        ]);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $exit);
        $this->assertStringContainsString('start date cannot be after the end date', $output->fetch());
    }

    /**
     * @dataProvider commandProvider
     */
    public function testMissingLogFile(string $commandClass): void
    {
        unlink($this->logPath);

        $command = new $commandClass();
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $exit);
        $this->assertStringContainsString('Log file not found', $output->fetch());
    }

    /**
     * @dataProvider commandProvider
     */
    public function testNoMatchingEntries(string $commandClass): void
    {
        file_put_contents($this->logPath, json_encode([
            'levelName' => 'debug',
            'timestamp' => '2024-01-01 12:00:00',
        ]) . PHP_EOL);

        $command = new $commandClass();
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exit);
        $this->assertStringContainsString('No matching log entries found', $output->fetch());
    }

    /**
     * @dataProvider commandProvider
     */
    public function testMalformedLogLineIgnored(string $commandClass): void
    {
        file_put_contents($this->logPath, "not-a-json\n");

        $command = new $commandClass();
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exit);
        $this->assertStringContainsString('No matching log entries found', $output->fetch());
    }

    /**
     * @dataProvider levelProvider
     */
    public function testMatchEntryOutput(string $commandClass, string $expectedLevel): void
    {
        $line = json_encode([
            'levelName' => $expectedLevel,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'message'   => 'Message here',
        ]);
        file_put_contents($this->logPath, $line . PHP_EOL);

        $command = new $commandClass();
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exit);
        $this->assertStringContainsString('Message here', $output->fetch());
    }

    /**
     * @throws ExceptionInterface
     * @throws \DateMalformedStringException
     */
    public function testLimitAddsDaysToStartDateOnly(): void
    {
        $start = '2024-01-01 00:00:00';
        $limit = 5;

        $command = new GetProcessedMessagesCommand();
        $input   = new ArrayInput([
            '--start' => $start,
            '--limit' => $limit,
        ]);

        $output  = new BufferedOutput();
        $logDate = (new DateTimeImmutable($start))->modify("+{$limit} days")->format('Y-m-d H:i:s');

        file_put_contents($this->logPath, json_encode([
            'levelName' => 'info',
            'timestamp' => $logDate,
            'message'   => 'Auto-inferred end',
        ]) . PHP_EOL);

        $exit = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exit);
        $this->assertStringContainsString('Auto-inferred end', $output->fetch());
    }

    public static function commandProvider(): array
    {
        return [
            [GetProcessedMessagesCommand::class],
            [GetFailedMessagesCommand::class],
        ];
    }

    public static function levelProvider(): array
    {
        return [
            [GetProcessedMessagesCommand::class, 'info'],
            [GetFailedMessagesCommand::class, 'error'],
        ];
    }
}
