<?php

declare(strict_types=1);

namespace Queue\Swoole\Command;

use Psr\Container\ContainerInterface;
use Queue\Swoole\PidManager;
use Swoole\Server as SwooleServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;
use function swoole_set_process_name;

#[AsCommand('queue:swoole:start')]
class StartCommand extends Command
{
    use IsRunningTrait;

    public PidManager $pidManager;

    public const DEFAULT_PROCESS_NAME = 'dotkernel-queue';

    public const DEFAULT_NUM_WORKERS = 4;

    public const HELP = <<<'EOH'
Start the web server. If --daemonize is provided, starts the server as a
background process and returns handling to the shell; otherwise, the
server runs in the current process.

Use --num-workers to control how many worker processes to start. If you
do not provide the option, 4 workers will be started.
EOH;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container, string $name = 'start')
    {
        $this->container = $container;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Start the web server.');
        $this->setHelp(self::HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pidManager = $this->container->get(PidManager::class);
        if ($this->isRunning()) {
            $output->writeln('<error>Server is already running!</error>');
            return 1;
        }

        $server      = $this->container->get(SwooleServer::class);
        $config      = $this->container->get('config');
        $processName = $config['dotkernel-queue-swoole']['swoole-server']['process-name']
            ?? self::DEFAULT_PROCESS_NAME;

        $pidManager = $this->pidManager;

        $server->on('start', function () use ($server, $pidManager, $processName, $output) {
            $output->writeln('<info>Server started...</info>');
            $pidManager->write($server->master_pid, $server->manager_pid);

            swoole_set_process_name(sprintf('%s-master', $processName));
        });

        $server->start();

        return 0;
    }
}
