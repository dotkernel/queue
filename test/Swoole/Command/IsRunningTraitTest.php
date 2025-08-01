<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Command;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Queue\Swoole\Command\IsRunningTrait;
use Queue\Swoole\PidManager;

class IsRunningTraitTest extends TestCase
{
    private object $traitUser;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->traitUser = new class {
            use IsRunningTrait;

            public PidManager $pidManager;
        };

        $this->traitUser->pidManager = $this->createMock(PidManager::class);
    }

    public function testIsRunningReturnsFalseWhenNoPids(): void
    {
        $this->traitUser->pidManager->method('read')->willReturn([]);
        $this->assertFalse($this->traitUser->isRunning());
    }

    public function testIsRunningReturnsFalseWhenPidsAreZero(): void
    {
        $this->traitUser->pidManager->method('read')->willReturn([0, 0]);
        $this->assertFalse($this->traitUser->isRunning());
    }
}
