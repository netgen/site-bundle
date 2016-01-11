<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SymlinkCommand extends ContainerAwareCommand
{
    /**
     * If true, command will destroy existing symlinks before recreating them.
     *
     * @var bool
     */
    protected $forceSymlinks = false;

    /**
     * Current environment.
     *
     * @var string
     */
    protected $environment = 'dev';

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fileSystem = null;

    /**
     * Files/directories that will not be symlinked in root and root_* folders.
     *
     * P.S. "settings" folder has special handling anyways
     *
     * @var array
     */
    protected $blacklistedItems = array(
        'offline_cro.html',
        'offline_eng.html',
        'settings',
    );

    /**
     * Verify that source file can be symlinked to destination and do symlinking.
     *
     * @param string $source
     * @param string $destination
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function verifyAndSymlinkFile($source, $destination, OutputInterface $output)
    {
        if (!$this->fileSystem->exists(dirname($destination))) {
            $this->fileSystem->mkdir(dirname($destination), 0755);
        }

        if ($this->fileSystem->exists($destination) && !is_file($destination)) {
            $output->writeln('<comment>' . basename($destination) . '</comment> already exists in <comment>' . dirname($destination) . '/</comment> and is not a file/symlink. Skipping...');

            return;
        }

        if (is_link($destination) && $this->forceSymlinks) {
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
                realpath(dirname($destination))
            ) . basename($source),
            $destination
        );
    }

    /**
     * Verify that source directory can be symlinked to destination and do symlinking.
     *
     * @param string $source
     * @param string $destination
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function verifyAndSymlinkDirectory($source, $destination, OutputInterface $output)
    {
        if ($this->fileSystem->exists($destination) && !is_link($destination)) {
            $output->writeln('<comment>' . basename($destination) . '</comment> already exists in <comment>' . dirname($destination) . '/</comment> and is not a symlink. Skipping...');

            return;
        }

        if (is_link($destination) && $this->forceSymlinks) {
            $this->fileSystem->remove($destination);
        }

        if ($this->fileSystem->exists($destination)) {
            $output->writeln('Skipped creating the symlink for <comment>' . basename($destination) . '</comment> in <comment>' . dirname($destination) . '/</comment>. Symlink already exists!');

            return;
        }

        $this->fileSystem->symlink(
            $this->fileSystem->makePathRelative(
                $source,
                realpath(dirname($destination))
            ),
            $destination
        );
    }
}
