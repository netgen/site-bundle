<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
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
            if ( !in_array( 'Netgen\\Bundle\\MoreBundle\\NetgenMoreProjectBundleInterface', class_implements( $bundle ) ) )
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

                if ( !file_exists( $item->getPathname() . '/extension.xml' ) )
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
     * Symlinks siteccesses from a legacy extension
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
                    if ( in_array( $item->getBasename(), $this->blacklistedFolders ) )
                    {
                        continue;
                    }

                    $files = self::findFilesInDirectory( $item->getPathname() );
                    foreach ( $files as $file )
                    {
                        $filePath = $this->fileSystem->makePathRelative(
                            realpath( dirname( $file ) ),
                            $directory->getPath()
                        ) . basename( $file );

                        $this->verifyAndSymlinkFile(
                            $file,
                            $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' ) . '/' . $filePath,
                            $output
                        );
                    }
                }
                else if ( $item->isDir() )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedFolders ) )
                    {
                        continue;
                    }

                    // Same directory exists in comparable bundle location, so we won't symlink it from here
                    if ( $this->fileSystem->exists( $bundleDirectory . '/' . $item->getBasename() ) )
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

                    // Same file exists in comparable bundle location, so we won't symlink it from here
                    if ( $this->fileSystem->exists( $bundleDirectory . '/' . $item->getBasename() ) )
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
