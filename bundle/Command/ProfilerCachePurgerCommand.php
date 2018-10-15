<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProfilerCachePurgerCommand extends ContainerAwareCommand
{
    /**
     * @var \Symfony\Component\HttpKernel\Profiler\Profiler
     */
    protected $profiler;

    protected function configure()
    {
        $this->setName('ngsite:profiler:clear-cache');
        $this->setDescription('Clears Profiler cache.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->profiler = $this->getContainer()->get('profiler');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->profiler->purge();
        $output->writeln('<info>Clearing Profiler cache finished.</info>');
    }
}
