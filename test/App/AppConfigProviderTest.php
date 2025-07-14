<?php

declare(strict_types=1);

namespace QueueTest\App;

use PHPUnit\Framework\TestCase;
use Queue\App\ConfigProvider;

class AppConfigProviderTest extends TestCase
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
