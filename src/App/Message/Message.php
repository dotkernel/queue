<?php

declare(strict_types=1);

namespace Queue\App\Message;

class Message
{
    public function __construct(
        public array $payload,
    ) {
    }
}
