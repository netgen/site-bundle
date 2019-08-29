<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use DirectoryIterator;
use Netgen\Bundle\SiteBundle\NetgenSiteProjectBundleInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class SymlinkProjectCommand extends SymlinkCommand
{
    /**
     * Files/directories that will not be symlinked in root and root_* folders.
     *
     * @var array
     */
    protected static $blacklistedItems = [
        'offline_cro.html',
        'offline_eng.html',
    ];
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel, Filesystem $fileSystem)
    {
        $this->kernel = $kernel;
        $this->fileSystem = $fileSystem;

        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them');
        $this->addOption('web-folder', null, InputOption::VALUE_OPTIONAL, 'Name of the webroot folder to use');
        $this->setDescription('Symlinks various project files and folders to their proper locations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->forceSymlinks = (bool) $input->getOption('force');

        $projectFilesPaths = [$this->kernel->getProjectDir() . '/src/Resources/symlink'];

        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$bundle instanceof NetgenSiteProjectBundleInterface) {
                continue;
            }

            $projectFilesPaths[] = $bundle->getPath() . '/Resources/symlink';
        }

        foreach ($projectFilesPaths as $projectFilesPath) {
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

        $path = $projectFilesPath . '/root_' . $this->kernel->getEnvironment() . '/';
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
                    $webFolderName = !empty($webFolderName) ? $webFolderName : 'public';

                    $destination = $this->kernel->getProjectDir() . '/' . $webFolderName . '/' . $item->getBasename();

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
