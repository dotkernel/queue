<?php

declare(strict_types=1);

namespace QueueTest\Swoole;

use PHPUnit\Framework\TestCase;
use Queue\Swoole\ConfigProvider;

class SwooleConfigProviderTest extends TestCase
{
    private array $config;

    public function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testHasDependencies(): void
    {
        $this->assertArrayHasKey('dependencies', $this->config);
    }
}
