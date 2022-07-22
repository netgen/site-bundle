<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use DirectoryIterator;
use Netgen\Bundle\SiteBundle\NetgenSiteProjectBundleInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function basename;
use function dirname;
use function in_array;
use function is_dir;
use function is_link;

class SymlinkProjectCommand extends SymlinkCommand
{
    /**
     * Files/directories that will not be symlinked in root and root_* folders.
     */
    protected static array $blacklistedItems = [
        'offline_cro.html',
        'offline_eng.html',
    ];

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them');
        $this->addOption('web-folder', null, InputOption::VALUE_OPTIONAL, 'Name of the webroot folder to use');
        $this->setDescription('Symlinks various project files and folders to their proper locations');
        $this->setName('ngsite:symlink:project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->forceSymlinks = (bool) $input->getOption('force');
        $this->environment = $this->getContainer()->get('kernel')->getEnvironment();
        $this->fileSystem = $this->getContainer()->get('filesystem');

        $kernel = $this->getContainer()->get('kernel');
        foreach ($kernel->getBundles() as $bundle) {
            if (!$bundle instanceof NetgenSiteProjectBundleInterface) {
                continue;
            }

            $projectFilesPath = $bundle->getPath() . '/Resources/symlink';

            if (!$this->fileSystem->exists($projectFilesPath) || !is_dir($projectFilesPath) || is_link($projectFilesPath)) {
                continue;
            }

            $this->symlinkProjectFiles($projectFilesPath, $input, $output);
        }

        return 0;
    }

    /**
     * Symlinks project files from a bundle.
     */
    protected function symlinkProjectFiles(string $projectFilesPath, InputInterface $input, OutputInterface $output): void
    {
        /** @var \DirectoryIterator[] $directories */
        $directories = [];

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
                    if (in_array($item->getBasename(), self::$blacklistedItems, true)) {
                        continue;
                    }

                    $webFolderName = $input->getOption('web-folder');
                    $webFolderName = !empty($webFolderName) ? $webFolderName : 'web';

                    $destination = $this->getContainer()->getParameter('kernel.project_dir') . '/' . $webFolderName . '/' . $item->getBasename();

                    if (!$this->fileSystem->exists(dirname($destination))) {
                        $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment>. Folder does not exist!');

                        continue;
                    }

                    if ($item->isDir()) {
                        $this->verifyAndSymlinkDirectory(
                            $item->getPathname(),
                            $destination,
                            $output,
                        );
                    } else {
                        $this->verifyAndSymlinkFile(
                            $item->getPathname(),
                            $destination,
                            $output,
                        );
                    }
                }
            }
        }
    }
}
