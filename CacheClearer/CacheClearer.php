<?php

namespace Asoc\CompassomatorBundle\CacheClearer;

use Asoc\CompassomatorBundle\Compassomator\ProcessRunner;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheClearer implements CacheClearerInterface {

	/**
	 * @var \Asoc\CompassomatorBundle\Compassomator\ProcessRunner
	 */
	private $processRunner;

	public function __construct(ProcessRunner $processRunner) {
		$this->processRunner = $processRunner;
	}

	/**
	 * Clears any caches necessary.
	 *
	 * @param string $cacheDir The cache directory.
	 */
	public function clear($cacheDir)
	{
		$this->run(new NullOutput());
	}

	public function run(OutputInterface $output) {
		$this->processRunner->prepare($output);
		$this->processRunner->abortWatchProjects();
		$this->processRunner->cleanProjects();
		$this->processRunner->cleanLogs();
	}
}