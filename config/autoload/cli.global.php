<?php

declare(strict_types=1);

use Dot\Cli\FileLockerInterface;
use Queue\Swoole\Command\GetFailedMessagesCommand;
use Queue\Swoole\Command\GetProcessedMessagesCommand;
use Queue\Swoole\Command\StartCommand;
use Queue\Swoole\Command\StopCommand;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\DebugCommand;

return [
    'dot_cli'                  => [
        'version'  => '1.0.0',
        'name'     => 'DotKernel CLI',
        'commands' => [
            "swoole:start"    => StartCommand::class,
            "swoole:stop"     => StopCommand::class,
            "messenger:start" => ConsumeMessagesCommand::class,
            "messenger:debug" => DebugCommand::class,
            "processed"       => GetProcessedMessagesCommand::class,
            "failed"          => GetFailedMessagesCommand::class,
        ],
    ],
    FileLockerInterface::class => [
        'enabled' => false,
        'dirPath' => getcwd() . '/data/lock',
    ],
];
