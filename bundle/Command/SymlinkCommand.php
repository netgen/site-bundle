<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function basename;
use function dirname;
use function is_file;
use function is_link;
use function realpath;

abstract class SymlinkCommand extends ContainerAwareCommand
{
    /**
     * If true, command will destroy existing symlinks before recreating them.
     */
    protected bool $forceSymlinks = false;

    /**
     * Current environment.
     */
    protected string $environment = 'dev';

    protected Filesystem $fileSystem;

    /**
     * Verify that source file can be symlinked to destination and do symlinking if it can.
     */
    protected function verifyAndSymlinkFile(string $source, string $destination, OutputInterface $output): void
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
    protected function verifyAndSymlinkDirectory(string $source, string $destination, OutputInterface $output): void
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
