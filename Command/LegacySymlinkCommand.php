<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DirectoryIterator;
use RuntimeException;

class LegacySymlinkCommand extends ContainerAwareCommand
{
    /**
     * If true, command will destroy existing symlinks before recreating them
     *
     * @var bool
     */
    protected $forceSymlinks = false;

    /**
     * Current environment
     *
     * @var string
     */
    protected $environment = 'dev';

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addOption( 'force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them' );
        $this->setDescription( 'Symlinks legacy siteaccesses and various other legacy files to their proper locations' );
        $this->setName( 'ngmore:legacy:symlink' );
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
        $fileSystem = $this->getContainer()->get( 'filesystem' );

        $legacyExtensions = array();

        $kernel = $this->getContainer()->get( 'kernel' );
        foreach ( $kernel->getBundles() as $bundle )
        {
            if ( !in_array( 'Netgen\\Bundle\\MoreBundle\\NetgenMoreProjectBundleInterface', class_implements( $bundle ) ) )
            {
                continue;
            }

            foreach ( new DirectoryIterator( $bundle->getPath() . '/ezpublish_legacy/' ) as $item )
            {
                if ( !$item->isDir() || $item->isDot() )
                {
                    continue;
                }

                if ( !file_exists( $item->getPathname() . '/extension.xml' ) )
                {
                    continue;
                }

                $legacyExtensions[] = $item->getPathname();
            }
        }

        foreach ( $legacyExtensions as $legacyExtensionPath )
        {
            $this->symlinkLegacyExtensionSiteAccesses( $legacyExtensionPath, $input, $output );
        }

        $overrideFolder = $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' ) . '/settings/override';

        if ( $fileSystem->exists( $overrideFolder ) && !is_link( $overrideFolder ) )
        {
            $output->writeln( '<comment>settings/override</comment> folder already exists in <comment>ezpublish_legacy/settings</comment> and is not a symlink. Skipping...' );
        }
        else
        {
            if ( $fileSystem->exists( $overrideFolder ) && !$this->forceSymlinks )
            {
                $output->writeln( 'Skipped creating the symlink for <comment>settings/override</comment> folder. Symlink already exists! (Use <comment>--force</comment> to override)' );
            }
            else
            {
                foreach ( $legacyExtensions as $legacyExtensionPath )
                {
                    $this->symlinkLegacyExtensionOverride( $legacyExtensionPath, $input, $output );
                }
            }
        }
    }

    /**
     * Symlinks siteccesses from a legacy extension
     *
     * @param string $legacyExtensionPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkLegacyExtensionSiteAccesses( $legacyExtensionPath, InputInterface $input, OutputInterface $output )
    {
        $legacyRootDir = $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' );
        $fileSystem = $this->getContainer()->get( 'filesystem' );

        foreach ( new DirectoryIterator( $legacyExtensionPath . '/root_' . $this->environment . '/settings/siteaccess/' ) as $item )
        {
            if ( !$item->isDir() || $item->isDot() )
            {
                continue;
            }

            $siteAccessDestination = $legacyRootDir . '/settings/siteaccess/' . $item->getBasename();

            if ( $fileSystem->exists( $siteAccessDestination ) && !is_link( $siteAccessDestination ) )
            {
                $output->writeln( '<comment>' . $item->getBasename() . '</comment> already exists in <comment>ezpublish_legacy/settings/siteaccess</comment> and is not a symlink. Skipping...' );
                continue;
            }

            if ( is_link( $siteAccessDestination ) && $this->forceSymlinks )
            {
                unlink( $siteAccessDestination );
            }

            if ( $fileSystem->exists( $siteAccessDestination ) )
            {
                $output->writeln( 'Skipped creating the symlink for <comment>' . $item->getBasename() . '</comment> siteaccess. Symlink already exists! (Use <comment>--force</comment> to override)' );
                continue;
            }

            $fileSystem->symlink(
                $fileSystem->makePathRelative(
                    $item->getPathname(),
                    $legacyRootDir
                ),
                $siteAccessDestination
            );
        }
    }

    /**
     * Symlinks override folder from a legacy extension
     *
     * @param string $legacyExtensionPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkLegacyExtensionOverride( $legacyExtensionPath, InputInterface $input, OutputInterface $output )
    {
        $legacyRootDir = $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' );
        $fileSystem = $this->getContainer()->get( 'filesystem' );

        $sourceFolder = $legacyExtensionPath . '/root_' . $this->environment . '/settings/override';
        if ( !$fileSystem->exists( $sourceFolder ) || !is_dir( $sourceFolder ) )
        {
            return;
        }

        $destinationFolder = $legacyRootDir . '/settings/override';

        if ( $fileSystem->exists( $destinationFolder ) && !is_link( $destinationFolder ) )
        {
            return;
        }

        if ( is_link( $destinationFolder ) && $this->forceSymlinks )
        {
            unlink( $destinationFolder );
        }

        if ( $fileSystem->exists( $destinationFolder ) )
        {
            return;
        }

        $fileSystem->symlink(
            $fileSystem->makePathRelative(
                $sourceFolder,
                $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/..'
            ),
            $destinationFolder
        );
    }
}
