<?php

declare(strict_types=1);

namespace QueueTest\App\Message;

use PHPUnit\Framework\TestCase;
use Queue\App\Message\ExampleMessage;

class ExampleMessageTest extends TestCase
{
    public function testMessageAccessors(): void
    {
        $admin = new ExampleMessage(["payload" => "test message payload"]);
        $this->assertSame(["payload" => "test message payload"], $admin->getPayload());
    }
}
