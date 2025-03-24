<?php

namespace Queue\App\Message;

class ExampleMessage
{
    public function __construct(
        private array $payload,
    )
    {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

}