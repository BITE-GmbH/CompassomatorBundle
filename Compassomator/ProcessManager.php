<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Finder\Finder;

class ProcessManager {

	/**
	 * @var \Symfony\Component\Finder\Finder
	 */
	private $finder;
	/**
	 * @var string
	 */
	private $runDir;

	public function __construct($runDir) {
		$this->runDir = $runDir;
		$this->finder = new Finder();

		if(!is_dir($this->runDir)) {
			mkdir($this->runDir, 0777, true);
		}
	}

	/**
	 * @return string
	 */
	public function getRunDir()
	{
		return $this->runDir;
	}

	/**
	 * @param $cmd
	 * @param $name
	 *
	 * @return string
	 */
	public function run($cmd, $name) {
		$cmd = sprintf('%s > %s & printf "%%u" $!', $cmd, $this->getLogFile($name));
		$pid = shell_exec($cmd);
		file_put_contents($this->getPidFile($name), $pid);

		return $pid;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function isRunning($name) {
		$pidFile = $this->getPidFile($name);
		return file_exists($pidFile);
	}

	/**
	 * @param $name
	 *
	 * @return int
	 */
	public function kill($name) {
		if(!$this->isRunning($name)) {
			return 0;
		}

		$pidFile = $this->getPidFile($name);
		$pid = $this->killByPidFile($pidFile);

		return $pid;
	}

	/**
	 * @param $pidFile
	 *
	 * @return int
	 */
	public function killByPidFile($pidFile) {
		$pid = intval(file_get_contents($pidFile));
		$cmd = sprintf('kill -9 %d > /dev/null 2>&1', $pid);
		shell_exec($cmd);

		unlink($pidFile);

		return $pid;
	}

	/**
	 * @return array
	 */
	public function killForgotten() {
		$remainingPids = $this->finder
			->files()
			->in($this->runDir)
			->name('*.pid');

		$pids = [];
		foreach($remainingPids as $pidFile) {
			$pids[] = $this->killByPidFile($pidFile);
		}

		return $pids;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function getLogFile($name) {
		return sprintf('%s/%s.log', $this->runDir, $name);
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function getPidFile($name) {
		return sprintf('%s/%s.pid', $this->runDir, $name);
	}

} 