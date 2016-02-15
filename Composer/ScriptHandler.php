<?php

namespace Netgen\Bundle\MoreBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as DistributionBundleScriptHandler;
use eZ\Bundle\EzPublishCoreBundle\Composer\ScriptHandler as CoreBundleScriptHandler;
use Composer\Script\CommandEvent;
use RuntimeException;

class ScriptHandler extends DistributionBundleScriptHandler
{
    /**
     * Symlinks various project files and folders to their proper locations.
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function installProjectSymlinks(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir (' . $appDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install project symlinks.' . PHP_EOL;

            return;
        }

        static::executeCommand($event, $appDir, 'ngmore:symlink:project', $options['process-timeout']);
    }

    /**
     * Dumps Assetic assets.
     *
     * Overriden to disable duplicate error output from the command.
     * Duplicate error message happens because Assetic already puts
     * the error output in the exception.
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function dumpAssets(CommandEvent $event)
    {
        try {
            CoreBundleScriptHandler::dumpAssets($event);
        } catch (RuntimeException $e) {
            throw new RuntimeException('An error occurred when executing "assetic:dump" command.');
        }
    }
}
