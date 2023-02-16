<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use DirectoryIterator;
use Netgen\Bundle\SiteBundle\NetgenSiteProjectBundleInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

use function basename;
use function dirname;
use function in_array;
use function is_dir;
use function is_file;
use function is_link;
use function realpath;

final class SymlinkProjectCommand extends Command
{
    /**
     * If true, command will destroy existing symlinks before recreating them.
     */
    private bool $forceSymlinks = false;

    /**
     * Files/directories that will not be symlinked in root and root_* folders.
     */
    private static array $blacklistedItems = [
        'offline_cro.html',
        'offline_eng.html',
    ];

    public function __construct(private KernelInterface $kernel, private Filesystem $fileSystem)
    {
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

        $projectFilesPaths = [$this->kernel->getProjectDir() . '/assets/symlink'];

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
    private function symlinkProjectFiles(string $projectFilesPath, InputInterface $input, OutputInterface $output): void
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

    /**
     * Verify that source file can be symlinked to destination and do symlinking if it can.
     */
    private function verifyAndSymlinkFile(string $source, string $destination, OutputInterface $output): void
    {
        if (!$this->fileSystem->exists(dirname($destination))) {
            $this->fileSystem->mkdir(dirname($destination), 0755);
        }

        if ($this->fileSystem->exists($destination) && !is_file($destination)) {
            $output->writeln('<comment>' . basename($destination) . '</comment> already exists in <comment>' . dirname($destination) . '/</comment> and is not a file/symlink. Skipping...');

            return;
        }

        if ($this->forceSymlinks && is_link($destination)) {
            $this->fileSystem->remove($destination);
        }

        if (is_file($destination) && !is_link($destination)) {
            if ($this->fileSystem->exists($destination . '.original')) {
                $output->writeln('Cannot create backup file <comment>' . basename($destination) . '.original</comment> in <comment>' . dirname($destination) . '/</comment>. Skipping...');

                return;
            }

            $this->fileSystem->rename($destination, $destination . '.original');
        }

        if ($this->fileSystem->exists($destination)) {
            if (is_link($destination)) {
                $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment>. Symlink already exists!');
            } else {
                $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment> due to an unknown error.');
            }

            return;
        }

        $this->fileSystem->symlink(
            $this->fileSystem->makePathRelative(
                dirname($source),
                realpath(dirname($destination)),
            ) . basename($source),
            $destination,
        );
    }

    /**
     * Verify that source directory can be symlinked to destination and do symlinking if it can.
     */
    private function verifyAndSymlinkDirectory(string $source, string $destination, OutputInterface $output): void
    {
        if ($this->fileSystem->exists($destination) && !is_link($destination)) {
            $output->writeln('<comment>' . basename($destination) . '</comment> already exists in <comment>' . dirname($destination) . '/</comment> and is not a symlink. Skipping...');

            return;
        }

        if ($this->forceSymlinks && is_link($destination)) {
            $this->fileSystem->remove($destination);
        }

        if ($this->fileSystem->exists($destination)) {
            $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment>. Symlink already exists!');

            return;
        }

        $this->fileSystem->symlink(
            $this->fileSystem->makePathRelative(
                $source,
                realpath(dirname($destination)),
            ),
            $destination,
        );
    }
}
