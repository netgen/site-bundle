<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerCachePurgerCommand extends Command
{
    /**
     * @var \Symfony\Component\HttpKernel\Profiler\Profiler
     */
    protected $profiler;

    /**
     * ProfilerCachePurgerCommand constructor.
     *
     * @param \Symfony\Component\HttpKernel\Profiler\Profiler $profiler
     */
    public function __construct(Profiler $profiler)
    {
        $this->profiler = $profiler;

        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('ngsite:profiler:clear-cache');
        $this->setDescription('Clears profiler cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->profiler->purge();
        $output->writeln('<info>Clearing Profiler cache finished.</info>');
    }
}
