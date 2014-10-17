<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Netgen\Bundle\MoreBundle\NetgenMoreProjectBundleInterface;
use Symfony\Component\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;

class SymlinkLegacyCommand extends SymlinkCommand
{
    /**
     * The list of folders available in standard distribution of eZ Publish Legacy
     *
     * @var array
     */
    protected $legacyDistFolders = array(
        'autoload',
        'benchmarks',
        'bin',
        'cronjobs',
        'design',
        'doc',
        'extension',
        'kernel',
        'lib',
        'schemas',
        'settings',
        'share',
        'support',
        'templates',
        'tests',
        'update',
        'var'
    );

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addOption( 'force', null, InputOption::VALUE_NONE, 'If set, it will destroy existing symlinks before recreating them' );
        $this->addOption( 'web-folder', null, InputOption::VALUE_OPTIONAL, 'Name of the webroot folder to use' );
        $this->setDescription( 'Symlinks legacy siteaccesses and various other legacy files to their proper locations' );
        $this->setName( 'ngmore:symlink:legacy' );
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

        $legacyExtensions = array();

        $kernel = $this->getContainer()->get( 'kernel' );
        foreach ( $kernel->getBundles() as $bundle )
        {
            if ( !$bundle instanceof NetgenMoreProjectBundleInterface )
            {
                continue;
            }

            if ( !$this->fileSystem->exists( $bundle->getPath() . '/ezpublish_legacy/' ) )
            {
                return;
            }

            foreach ( new DirectoryIterator( $bundle->getPath() . '/ezpublish_legacy/' ) as $item )
            {
                if ( !$item->isDir() || $item->isDot() )
                {
                    continue;
                }

                if ( !$this->fileSystem->exists( $item->getPathname() . '/extension.xml' ) )
                {
                    continue;
                }

                $legacyExtensions[$bundle->getPath()] = $item->getPathname();
            }
        }

        foreach ( $legacyExtensions as $bundlePath => $legacyExtensionPath )
        {
            $this->symlinkLegacyExtensionSiteAccesses( $legacyExtensionPath, $input, $output );
            $this->symlinkLegacyExtensionOverride( $legacyExtensionPath, $input, $output );
            $this->symlinkLegacyExtensionFiles( $bundlePath, $legacyExtensionPath, $input, $output );
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

        if ( !$this->fileSystem->exists( $legacyExtensionPath . '/root_' . $this->environment . '/settings/siteaccess/' ) )
        {
            return;
        }

        foreach ( new DirectoryIterator( $legacyExtensionPath . '/root_' . $this->environment . '/settings/siteaccess/' ) as $item )
        {
            if ( !$item->isDir() || $item->isDot() )
            {
                continue;
            }

            $siteAccessDestination = $legacyRootDir . '/settings/siteaccess/' . $item->getBasename();

            $this->verifyAndSymlinkDirectory( $item->getPathname(), $siteAccessDestination, $output );
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

        $sourceFolder = $legacyExtensionPath . '/root_' . $this->environment . '/settings/override';
        if ( !$this->fileSystem->exists( $sourceFolder ) || !is_dir( $sourceFolder ) )
        {
            return;
        }

        $this->verifyAndSymlinkDirectory( $sourceFolder, $legacyRootDir . '/settings/override', $output );
    }

    /**
     * Symlinks files from a legacy extension
     *
     * @param string $bundlePath
     * @param string $legacyExtensionPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkLegacyExtensionFiles( $bundlePath, $legacyExtensionPath, InputInterface $input, OutputInterface $output )
    {
        /** @var \DirectoryIterator[] $directories */
        $directories = array();

        $path = $legacyExtensionPath . '/root/';
        if ( $this->fileSystem->exists( $path ) )
        {
            $directories[$bundlePath . '/Resources/symlink/root'] = new DirectoryIterator( $path );
        }

        $path = $legacyExtensionPath . '/root_' . $this->environment . '/';
        if ( $this->fileSystem->exists( $path ) )
        {
            $directories[$bundlePath . '/Resources/symlink/root_' . $this->environment] = new DirectoryIterator( $path );
        }

        foreach ( $directories as $bundleDirectory => $directory )
        {
            foreach ( $directory as $item )
            {
                if ( $item->isDot() || $item->isLink() )
                {
                    continue;
                }

                if ( $item->isDir() && in_array( $item->getBasename(), $this->legacyDistFolders ) )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedItems ) )
                    {
                        continue;
                    }

                    foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $item->getPathname() ) ) as $subItem )
                    {
                        /** @var \SplFileInfo $subItem */
                        if ( $subItem->isFile() && !$subItem->isLink() )
                        {
                            $filePath = $this->fileSystem->makePathRelative(
                                realpath( $subItem->getPath() ),
                                $directory->getPath()
                            ) . $subItem->getBasename();

                            $this->verifyAndSymlinkFile(
                                $subItem->getPathname(),
                                $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' ) . '/' . $filePath,
                                $output
                            );
                        }
                    }
                }
                else if ( $item->isDir() || $item->isFile() )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedItems ) )
                    {
                        continue;
                    }

                    // Same directory/file exists in comparable bundle location, so we won't symlink it from here
                    if ( $this->fileSystem->exists( $bundleDirectory . '/' . $item->getBasename() ) )
                    {
                        continue;
                    }

                    $webFolderName = $input->getOption( 'web-folder' );
                    $webFolderName = !empty( $webFolderName ) ? $webFolderName : 'web';

                    $destination = $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/../' . $webFolderName . '/' . $item->getBasename();

                    if ( !$this->fileSystem->exists( dirname( $destination ) ) )
                    {
                        $output->writeln( 'Skipped creating the symlink for <comment>' . basename( $destination ) . '</comment> in <comment>' . dirname( $destination ) . '/</comment>. Folder does not exist!' );
                        continue;
                    }

                    if ( $item->isDir() )
                    {
                        $this->verifyAndSymlinkDirectory(
                            $item->getPathname(),
                            $destination,
                            $output
                        );
                    }
                    else
                    {
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
