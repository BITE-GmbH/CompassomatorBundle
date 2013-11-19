<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Process\ProcessBuilder;

abstract class ProcessUtils {

	public static function getCompassomatorWatcher($compassProjectRoot, $bundleMapFile, $bundlePublicMapFile) {
		return self::getCompassomator('watch', $compassProjectRoot, $bundleMapFile, $bundlePublicMapFile);
	}

	public static function getCompassomatorCompiler($compassProjectRoot, $bundleMapFile, $bundlePublicMapFile) {
		return self::getCompassomator('compile', $compassProjectRoot, $bundleMapFile, $bundlePublicMapFile);
	}

	public static function getCompass($compassProjectRoot) {
		$builder = new ProcessBuilder([
		                              'compass',
		                              'clean'
		                              ]);
		$builder->setWorkingDirectory($compassProjectRoot);

		return $builder->getProcess();
	}

	public static function getAssetic($watch = false) {
		$builder = new ProcessBuilder([
		                              'php',
		                              'app/console',
		                              'assetic:dump'
		                              ]);

		if($watch) {
			$builder->add('--watch');
		}

		return $builder->getProcess();
	}

	private static function getCompassomator($script, $compassProjectRoot, $bundleMapFile, $bundlePublicMapFile) {
		$builder = new ProcessBuilder([
		                              'ruby',
		                              sprintf('%s/Ruby/lib/compassomator/%s.rb', __DIR__.'/..', $script),
		                              $compassProjectRoot,
		                              $bundleMapFile,
		                              $bundlePublicMapFile
		                              ]);

		return $builder->getProcess();
	}

	private final function __construct() {}

} 