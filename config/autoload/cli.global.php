<?php

use Dot\Cli\FileLockerInterface;

return [
    'dot_cli'                  => [
        'version'  => '1.0.0',
        'name'     => 'DotKernel CLI',
        'commands' => [
            "swoole:start" => \Queue\Swoole\Command\StartCommand::class,
            "swoole:stop" => \Queue\Swoole\Command\StopCommand::class,
            "messenger:start" => \Symfony\Component\Messenger\Command\ConsumeMessagesCommand::class,
            "messenger:debug" => \Symfony\Component\Messenger\Command\DebugCommand::class
        ],
    ],
    FileLockerInterface::class => [
        'enabled' => false,
        'dirPath' => getcwd() . '/data/lock',
    ],
];