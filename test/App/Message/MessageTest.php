<?php

declare(strict_types=1);

namespace QueueTest\App\Message;

use PHPUnit\Framework\TestCase;
use Queue\App\Message\Message;

class MessageTest extends TestCase
{
    public function testMessageAccessors(): void
    {
        $admin = new Message(["payload" => "test message payload"]);
        $this->assertSame(["payload" => "test message payload"], $admin->getPayload());
    }
}
