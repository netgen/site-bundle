<?php

namespace Netgen\Bundle\MoreBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as DistributionBundleScriptHandler;
use Composer\Script\CommandEvent;
use Symfony\Component\Process\ProcessBuilder;

class ScriptHandler extends DistributionBundleScriptHandler
{
    /**
     * Symlinks legacy siteaccesses and various other legacy files to their proper locations.
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function installLegacySymlinks(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir (' . $appDir . ') specified in composer.json was not found in ' . getcwd() . ', can not install legacy symlinks.' . PHP_EOL;

            return;
        }

        static::executeCommand($event, $appDir, 'ngmore:symlink:legacy', $options['process-timeout']);
    }

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
     * Generates legacy autoloads.
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function generateLegacyAutoloads(CommandEvent $event)
    {
        return self::generateLegacyAutoloadsArray($event);
    }

    /**
     * Generates legacy autoloads for kernel overrides.
     *
     * @param $event \Composer\Script\CommandEvent
     */
    public static function generateLegacyKernelOverrideAutoloads(CommandEvent $event)
    {
        return self::generateLegacyAutoloadsArray($event, true);
    }

    /**
     * Generates legacy autoloads.
     *
     * @param $event \Composer\Script\CommandEvent
     * @param bool $generateKernelOverrideAutoloads
     *
     * @param $event \Composer\Script\CommandEvent
     */
    protected static function generateLegacyAutoloadsArray(
        CommandEvent $event,
        $generateKernelOverrideAutoloads = false
    ) {
        $options = self::getOptions($event);

        $currentWorkingDirectory = getcwd();
        $legacyRootDir = $currentWorkingDirectory . '/' . $options['ezpublish-legacy-dir'];

        if (!is_dir($legacyRootDir)) {
            echo 'The ezpublish-legacy-dir (' . $options['ezpublish-legacy-dir'] . ') specified in composer.json was not found in ' . $currentWorkingDirectory . ', can not generate legacy autoloads.' . PHP_EOL;

            return;
        }

        chdir($legacyRootDir);

        $processParameters = array(
            'php',
            'bin/php/ezpgenerateautoloads.php',
        );

        if ($generateKernelOverrideAutoloads) {
            $processParameters[] = '-o';
        }

        $processBuilder = new ProcessBuilder($processParameters);

        $process = $processBuilder->getProcess();

        $process->setTimeout(3600);
        $process->run(
            function ($type, $buffer) {
                echo $buffer;
            }
        );

        chdir($currentWorkingDirectory);
    }
}
