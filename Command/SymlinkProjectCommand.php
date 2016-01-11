<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Netgen\Bundle\MoreBundle\NetgenMoreProjectBundleInterface;
use DirectoryIterator;

class SymlinkProjectCommand extends SymlinkCommand
{
    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them');
        $this->addOption('web-folder', null, InputOption::VALUE_OPTIONAL, 'Name of the webroot folder to use');
        $this->setDescription('Symlinks various project files and folders to their proper locations');
        $this->setName('ngmore:symlink:project');
    }

    /**
     * Runs the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->forceSymlinks = (bool)$input->getOption('force');
        $this->environment = $this->getContainer()->get('kernel')->getEnvironment();
        $this->fileSystem = $this->getContainer()->get('filesystem');

        $kernel = $this->getContainer()->get('kernel');
        foreach ($kernel->getBundles() as $bundle) {
            if (!$bundle instanceof NetgenMoreProjectBundleInterface) {
                continue;
            }

            $projectFilesPath = $bundle->getPath() . '/Resources/symlink';

            if (!$this->fileSystem->exists($projectFilesPath) || !is_dir($projectFilesPath) || is_link($projectFilesPath)) {
                continue;
            }

            $this->symlinkProjectFiles($projectFilesPath, $input, $output);
        }
    }

    /**
     * Symlinks project files from a bundle.
     *
     * @param string $projectFilesPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkProjectFiles($projectFilesPath, InputInterface $input, OutputInterface $output)
    {
        /** @var \DirectoryIterator[] $directories */
        $directories = array();

        $path = $projectFilesPath . '/root_' . $this->environment . '/';
        if ($this->fileSystem->exists($path) && is_dir($path)) {
            $directories[] = new DirectoryIterator($path);
        }

        $path = $projectFilesPath . '/root/';
        if ($this->fileSystem->exists($path) && is_dir($path)) {
            $directories[] = new DirectoryIterator($path);
        }

        foreach ($directories as $directory) {
            foreach ($directory as $item) {
                if ($item->isDot() || $item->isLink()) {
                    continue;
                }

                if ($item->isDir() || $item->isFile()) {
                    if (in_array($item->getBasename(), $this->blacklistedItems)) {
                        continue;
                    }

                    $webFolderName = $input->getOption('web-folder');
                    $webFolderName = !empty($webFolderName) ? $webFolderName : 'web';

                    $destination = $this->getContainer()->getParameter('kernel.root_dir') . '/../' . $webFolderName . '/' . $item->getBasename();

                    if (!$this->fileSystem->exists(dirname($destination))) {
                        $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment>. Folder does not exist!');
                        continue;
                    }

                    if ($item->isDir()) {
                        $this->verifyAndSymlinkDirectory(
                            $item->getPathname(),
                            $destination,
                            $output
                        );
                    } else {
                        $this->verifyAndSymlinkFile(
                            $item->getPathname(),
                            $destination,
                            $output
                        );
                    }
                }
            }
        }
    }
}
