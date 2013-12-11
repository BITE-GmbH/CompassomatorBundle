<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Process\ProcessBuilder;

abstract class ProcessUtils
{
    public static function getCompassomatorWatcher(
        $compassProjectRoot,
        $bundleMapFile,
        $bundlePublicMapFile,
        $verbosity
    ) {
        return self::getCompassomator('watch', $compassProjectRoot, $bundleMapFile, $bundlePublicMapFile, $verbosity);
    }

    public static function getCompassomatorCompiler(
        $compassProjectRoot,
        $bundleMapFile,
        $bundlePublicMapFile,
        $verbosity
    ) {
        return self::getCompassomator('compile', $compassProjectRoot, $bundleMapFile, $bundlePublicMapFile, $verbosity);
    }

    public static function getCompass($compassProjectRoot, $verbosity = 0)
    {
        $builder = new ProcessBuilder(array(
            'compass',
            'clean'
        ));
        $builder->setWorkingDirectory($compassProjectRoot);

        return $builder->getProcess();
    }

    public static function getAssetic($watch = false, $env = null, $verbosity = 0)
    {
        $builder = new ProcessBuilder(array(
            'php',
            'app/console',
            'assetic:dump'
        ));

        if ($env !== null) {
            $builder->add('--env')->add($env);
        }
        if ($verbosity > 0) {
            $builder->add('-'.str_repeat('v', $verbosity));
        }

        if ($watch) {
            $builder->add('--watch');
        }

        return $builder->getProcess();
    }

    private static function getCompassomator(
        $script,
        $compassProjectRoot,
        $bundleMapFile,
        $bundlePublicMapFile,
        $verbosity = 0
    ) {
        $builder = new ProcessBuilder(array(
            'ruby',
            sprintf('%s/Ruby/lib/compassomator/%s.rb', __DIR__.'/..', $script),
            $compassProjectRoot,
            $bundleMapFile,
            $bundlePublicMapFile
        ));

        if ($verbosity > 1) {
            $builder->add('true');
        } else {
            $builder->add('false');
        }

        return $builder->getProcess();
    }

    private final function __construct()
    {
    }
}