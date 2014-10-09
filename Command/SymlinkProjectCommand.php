<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DirectoryIterator;

class SymlinkProjectCommand extends SymlinkCommand
{
    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addOption( 'force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them' );
        $this->setDescription( 'Symlinks various project files and folders to their proper locations' );
        $this->setName( 'ngmore:symlink:project' );
    }

    /**
     * Runs the command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->forceSymlinks = (bool)$input->getOption( 'force' );
        $this->environment = $this->getContainer()->get( 'kernel' )->getEnvironment();
        $this->fileSystem = $this->getContainer()->get( 'filesystem' );

        $kernel = $this->getContainer()->get( 'kernel' );
        foreach ( $kernel->getBundles() as $bundle )
        {
            if ( !in_array( 'Netgen\\Bundle\\MoreBundle\\NetgenMoreProjectBundleInterface', class_implements( $bundle ) ) )
            {
                continue;
            }

            $projectFilesPath = $bundle->getPath() . '/Resources/symlink';

            if ( !$this->fileSystem->exists( $projectFilesPath ) )
            {
                return;
            }

            $this->symlinkProjectFiles( $projectFilesPath, $input, $output );
        }
    }

    /**
     * Symlinks project files from a bundle
     *
     * @param string $projectFilesPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkProjectFiles( $projectFilesPath, InputInterface $input, OutputInterface $output )
    {
        /** @var \DirectoryIterator[] $directories */
        $directories = array();

        $path = $projectFilesPath . '/root/';
        if ( $this->fileSystem->exists( $path ) )
        {
            $directories[] = new DirectoryIterator( $path );
        }

        $path = $projectFilesPath . '/root_' . $this->environment . '/';
        if ( $this->fileSystem->exists( $path ) )
        {
            $directories[] = new DirectoryIterator( $path );
        }

        foreach ( $directories as $directory )
        {
            foreach ( $directory as $item )
            {
                if ( $item->isDot() || $item->isLink() )
                {
                    continue;
                }

                if ( $item->isDir() )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedFolders ) )
                    {
                        continue;
                    }

                    $this->verifyAndSymlinkDirectory(
                        $item->getPathname(),
                        $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/../web/' . $item->getBasename(),
                        $output
                    );
                }
                else if ( $item->isFile() )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedFiles ) )
                    {
                        continue;
                    }

                    $this->verifyAndSymlinkFile(
                        $item->getPathname(),
                        $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/../web/' . $item->getBasename(),
                        $output
                    );
                }
            }
        }
    }
}
