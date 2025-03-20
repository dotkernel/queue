<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Messenger as SymfonyMessenger;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

$container = require 'config/container.php';

$application = new Application();
$application->setCommandLoader(new ContainerCommandLoader(
    $container,
    [
        'consume' => SymfonyMessenger\Command\ConsumeMessagesCommand::class,
        'debug:messenger' => SymfonyMessenger\Command\DebugCommand::class,
    ]
));

$application->run();