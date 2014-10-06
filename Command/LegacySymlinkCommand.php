<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Netgen\Bundle\GeneratorBundle\Helper\FileHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use DirectoryIterator;

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
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fileSystem = null;

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
     * Files that will not be symlinked in root and root_* folders
     *
     * @var array
     */
    protected $blacklistedFiles = array(
        'config.php',
        'offline_cro.html',
        'offline_eng.html'
    );

    /**
     * Directories that will not be symlinked in root and root_* folders
     *
     * P.S. "settings" folder has special handling anyways
     *
     * @var array
     */
    protected $blacklistedFolders = array(
        'settings'
    );

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

                $legacyExtensions[] = $item->getPathname();
            }
        }

        foreach ( $legacyExtensions as $legacyExtensionPath )
        {
            $this->symlinkLegacyExtensionSiteAccesses( $legacyExtensionPath, $input, $output );
        }

        $overrideFolder = $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' ) . '/settings/override';

        if ( $this->fileSystem->exists( $overrideFolder ) && !is_link( $overrideFolder ) )
        {
            $output->writeln( '<comment>settings/override</comment> folder already exists in <comment>ezpublish_legacy/settings</comment> and is not a symlink. Skipping...' );
        }
        else
        {
            if ( $this->fileSystem->exists( $overrideFolder ) && !$this->forceSymlinks )
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

        foreach ( $legacyExtensions as $legacyExtensionPath )
        {
            $this->symlinkLegacyExtensionFiles( $legacyExtensionPath, $input, $output );
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

            $this->verifyAndSymlinkDirectory( $item->getPathname(), $siteAccessDestination );
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

        $this->verifyAndSymlinkDirectory( $sourceFolder, $legacyRootDir . '/settings/override' );
    }

    /**
     * Symlinks siteccesses from a legacy extension
     *
     * @param string $legacyExtensionPath
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function symlinkLegacyExtensionFiles( $legacyExtensionPath, InputInterface $input, OutputInterface $output )
    {
        if ( !$this->fileSystem->exists( $legacyExtensionPath . '/root/' ) )
        {
            return;
        }

        /** @var \DirectoryIterator[] $directories */
        $directories = array(
            new DirectoryIterator( $legacyExtensionPath . '/root/' ),
            new DirectoryIterator( $legacyExtensionPath . '/root_' . $this->environment . '/' ),
        );

        foreach ( $directories as $directory )
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

                    $files = FileHelper::findFilesInDirectory( $item->getPathname() );
                    foreach ( $files as $file )
                    {
                        $filePath = $this->fileSystem->makePathRelative(
                            realpath( dirname( $file ) ),
                            $directory->getPath()
                        ) . basename( $file );

                        $this->verifyAndSymlinkFile(
                            $file,
                            $this->getContainer()->getParameter( 'ezpublish_legacy.root_dir' ) . '/' . $filePath
                        );
                    }
                }
                else if ( $item->isDir() )
                {
                    if ( in_array( $item->getBasename(), $this->blacklistedFolders ) )
                    {
                        continue;
                    }

                    $this->verifyAndSymlinkDirectory(
                        $item->getPathname(),
                        $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/../web/' . $item->getBasename()
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
                        $this->getContainer()->getParameter( 'kernel.root_dir' ) . '/../web/' . $item->getBasename()
                    );
                }
            }
        }
    }

    /**
     * Verify that source file can be symlinked to destination and do symlinking
     *
     * @param string $source
     * @param string $destination
     */
    protected function verifyAndSymlinkFile( $source, $destination )
    {
        if ( !$this->fileSystem->exists( dirname( $destination ) ) )
        {
            $this->fileSystem->mkdir( dirname( $destination ), 0755 );
        }

        if ( $this->fileSystem->exists( $destination ) && !is_file( $destination ) )
        {
            return;
        }

        if ( is_link( $destination ) && $this->forceSymlinks )
        {
            unlink( $destination );
        }

        if ( is_file( $destination ) && !is_link( $destination ) )
        {
            if ( $this->fileSystem->exists( $destination . '.original' ) )
            {
                return;
            }

            $this->fileSystem->rename( $destination, $destination . '.original' );
        }

        if ( $this->fileSystem->exists( $destination ) )
        {
            return;
        }

        $this->fileSystem->symlink(
            $this->fileSystem->makePathRelative(
                dirname( $source ),
                realpath( dirname( $destination ) )
            ) . basename( $source ),
            $destination
        );
    }

    /**
     * Verify that source directory can be symlinked to destination and do symlinking
     *
     * @param string $source
     * @param string $destination
     */
    protected function verifyAndSymlinkDirectory( $source, $destination )
    {
        if ( $this->fileSystem->exists( $destination ) && !is_link( $destination ) )
        {
            return;
        }

        if ( is_link( $destination ) && $this->forceSymlinks )
        {
            unlink( $destination );
        }

        if ( $this->fileSystem->exists( $destination ) )
        {
            return;
        }

        $this->fileSystem->symlink(
            $this->fileSystem->makePathRelative(
                $source,
                realpath( dirname( $destination ) )
            ),
            $destination
        );
    }
}
