<?php

declare(strict_types=1);

namespace QueueTest\Swoole;

use PHPUnit\Framework\TestCase;
use Queue\Swoole\PidManager;
use RuntimeException;

use function chmod;
use function file_exists;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

class PidManagerTest extends TestCase
{
    private string $tempPidFile;

    protected function setUp(): void
    {
        $this->tempPidFile = sys_get_temp_dir() . '/test.pid';
        if (file_exists($this->tempPidFile)) {
            unlink($this->tempPidFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempPidFile)) {
            unlink($this->tempPidFile);
        }
    }

    public function testWriteAndReadPids(): void
    {
        $manager = new PidManager($this->tempPidFile);

        $manager->write(12345, 67890);

        $result = $manager->read();

        $this->assertSame(['12345', '67890'], $result);
        $this->assertFileExists($this->tempPidFile);
    }

    public function testDeleteRemovesPidFile(): void
    {
        file_put_contents($this->tempPidFile, 'dummyData');

        $manager = new PidManager($this->tempPidFile);
        $deleted = $manager->delete();

        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($this->tempPidFile);
    }

    public function testDeleteReturnsFalseIfFileNotWritable(): void
    {
        file_put_contents($this->tempPidFile, 'dummyData');
        chmod($this->tempPidFile, 0444);

        $manager = new PidManager($this->tempPidFile);
        $result  = $manager->delete();

        $this->assertFalse($result);

        chmod($this->tempPidFile, 0644);
    }

    public function testWriteThrowsWhenFileNotWritable(): void
    {
        $unwritableDir = sys_get_temp_dir() . '/unwritable_dir';
        mkdir($unwritableDir, 0444);
        $unwritableFile = $unwritableDir . '/file.pid';

        $manager = new PidManager($unwritableFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not writable/');

        try {
            $manager->write(1, 2);
        } finally {
            chmod($unwritableDir, 0755);
            rmdir($unwritableDir);
        }
    }
}
