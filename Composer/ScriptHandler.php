<?php

namespace Netgen\Bundle\MoreBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as DistributionBundleScriptHandler;
use Composer\Script\CommandEvent;

class ScriptHandler extends DistributionBundleScriptHandler
{
    /**
     * Symlinks various project files and folders to their proper locations
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function installProjectSymlinks( CommandEvent $event )
    {
        $options = self::getOptions( $event );
        $appDir = $options['symfony-app-dir'];

        if ( !is_dir( $appDir ) )
        {
            echo 'The symfony-app-dir (' . $appDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install project symlinks.' . PHP_EOL;
            return;
        }

        static::executeCommand( $event, $appDir, 'ngmore:symlink:project', $options['process-timeout'] );
    }
}
