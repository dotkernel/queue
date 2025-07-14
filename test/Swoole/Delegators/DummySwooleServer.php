<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Delegators;

use Swoole\Server;

class DummySwooleServer extends Server
{
    /** @var array<string, callable> */
    public array $callbacks = [];

    public function __construct()
    {
        parent::__construct('127.0.0.1', 0);
    }

    /**
     * @param string   $eventName
     * @param callable $callback
     */
    public function on($eventName, $callback): bool
    {
        $this->callbacks[$eventName] = $callback;
        return true;
    }

    /**
     * @param int|string $fd
     * @param string $data
     * @param int $serverSocket
     */
    public function send($fd, $data, $serverSocket = -1): bool
    {
        return true;
    }
}
