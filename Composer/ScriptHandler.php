<?php

namespace Netgen\Bundle\MoreBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as DistributionBundleScriptHandler;
use eZ\Bundle\EzPublishCoreBundle\Composer\ScriptHandler as CoreBundleScriptHandler;
use Composer\Script\Event;
use RuntimeException;

class ScriptHandler extends DistributionBundleScriptHandler
{
    /**
     * Symlinks various project files and folders to their proper locations.
     *
     * @param $event \Composer\Script\Event
     */
    public static function installProjectSymlinks(Event $event)
    {
        $options = self::getOptions($event);
        $binDir = $options['symfony-bin-dir'];

        if (!is_dir($binDir)) {
            echo 'The symfony-bin-dir (' . $binDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install project symlinks.' . PHP_EOL;

            return;
        }

        static::executeCommand($event, $binDir, 'ngmore:symlink:project', $options['process-timeout']);
    }

    /**
     * Dumps Assetic assets.
     *
     * Overriden to disable duplicate error output from the command.
     * Duplicate error message happens because Assetic already puts
     * the error output in the exception.
     *
     * @param $event \Composer\Script\Event
     */
    public static function dumpAssets(Event $event)
    {
        try {
            CoreBundleScriptHandler::dumpAssets($event);
        } catch (RuntimeException $e) {
            throw new RuntimeException('An error occurred when executing "assetic:dump" command.');
        }
    }
}
