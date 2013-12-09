<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class ProcessRunner {

	/**
	 * @var ProcessManager
	 */
	private $processManager;
	/**
	 * @var BundleFinder
	 */
	private $bundleFinder;
	/**
	 * @var string[]
	 */
	protected $compassProjectRoots;
	/**
	 * @var string[]
	 */
	protected $compassImportPaths;
	/**
	 * JSON file containing the path information for bundle root directories
	 *
	 * eg. BundleName => src/Acme/DemoBundle
	 *
	 * @var string
	 */
	protected $bundleMapFile;
	/**
	 * JSON file containing the path information for bundle public directories (relative to the assetic css output
	 * directory)
	 *
	 * eg. BundleName => ../bundles/bundlename
	 *
	 * @var
	 */
	protected $bundlePublicMapFile;
	/**
	 * @var boolean
	 */
	private $isPrepared;
	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * Kernel environment
	 * @var string
	 */
	private $env;

	public function __construct(BundleFinder $bundleFinder, ProcessManager $processManager, $env = null)
	{
		$this->bundleFinder        = $bundleFinder;
		$this->processManager      = $processManager;
		$this->env = $env;

		$runDir = $processManager->getRunDir();
		$this->bundleMapFile = $runDir.'/bundlemap.json';
		$this->bundlePublicMapFile = $runDir.'/bundlepublicmap.json';

		$this->isPrepared = false;
	}

	public function prepare(OutputInterface $output = null) {
		if($output === null) {
			$this->output = new NullOutput();
		}
		else {
			$this->output = $output;
		}

		// find all the active bundles and their roots
		$this->compassImportPaths = $this->bundleFinder->findImportPaths();

		// find compass configs in the active bundles
		$this->compassProjectRoots = $this->bundleFinder->findCompassProjects();

		// find public directories for each active bundle
		$publicDirs = $this->bundleFinder->findPublicBundleDirs();

		// dump the bundle map (bundle name => bundle root) into a json file which is later used by the ruby side
		file_put_contents($this->bundleMapFile, json_encode($this->compassImportPaths, JSON_PRETTY_PRINT));
		file_put_contents($this->bundlePublicMapFile, json_encode($publicDirs, JSON_PRETTY_PRINT));

		$this->isPrepared = true;
	}

	public function abortWatchProjects() {
		$this->output->writeln('<comment>Stopping watch...</comment>');

		// stop compass watchers
		foreach ($this->compassProjectRoots as $bundleName => $compassProjectRoot)
		{
			$name = sprintf('compass-%s-watch', $bundleName);
			$this->output->write(sprintf('  > compass watch: <comment>%s</comment>', $bundleName));
			$pid = $this->processManager->kill($name);

			if($pid > 0) {
				$this->output->writeln(sprintf(' (PID: %d)', $pid));
			}
			else {
				$this->output->writeln(' (not running)');
			}
		}

		// stop assetc watch
		$this->output->write('  > assetic watch');
		$pid = $this->processManager->kill('assetic-watch');

		if($pid > 0) {
			$this->output->writeln(sprintf(' (PID: %d)', $pid));
		}
		else {
			$this->output->writeln(' (not running)');
		}

		// kill any process spawned by the compassomator which is not in the active bundle list anymore.
		// happens when compassomator:watch is running and a bundle or it's config.rb is removed.
		$forgottenPids = $this->processManager->killForgotten();
		if(count($forgottenPids)) {
			$this->output->writeln(sprintf('  > killed forgotten processes: %s', implode(', ', $forgottenPids)));
		}
	}

	public function watchProjects() {
		$this->output->writeln('<info>Starting watch...</info>');

		// build the compass watchers
		foreach ($this->compassProjectRoots as $bundleName => $compassProjectRoot)
		{
			$name = sprintf('compass-%s-watch', $bundleName);

			// start watching for changes
			$process = ProcessUtils::getCompassomatorWatcher($compassProjectRoot, $this->bundleMapFile, $this->bundlePublicMapFile, $this->output->getVerbosity());
			$this->output->write(sprintf('  > compass watch: <info>%s</info>', $bundleName));
			$pid = $this->processManager->run($process, $name);
			$this->output->writeln(sprintf(' (PID: %d)', $pid));
		}

		// run assetic:watch
		$process = ProcessUtils::getAssetic(true, $this->env, $this->output->getVerbosity());
		$this->output->write('  > assetic watch');
		$pid = $this->processManager->run($process, 'assetic-watch');
		$this->output->writeln(sprintf(' (PID: %d)', $pid));
	}

	public function compileProjects() {
		$that = $this;
		foreach ($this->compassProjectRoots as $bundleName => $compassProjectRoot)
		{
			$name = sprintf('compass-%s-compile', $bundleName);

			$this->output->writeln(sprintf('  > compass compile: <info>%s</info>', $bundleName));
			$logFile = $this->processManager->getLogFile($name);
			$process = ProcessUtils::getCompassomatorCompiler($compassProjectRoot, $this->bundleMapFile, $this->bundlePublicMapFile, $this->output->getVerbosity());
			$process->run(function ($type, $buffer) use(&$that) {
				$that->writeCompassOutput($buffer);
			});
			file_put_contents($logFile, $process->getOutput() . "\n" . $process->getErrorOutput());
		}

		// dump the assets once (because :watch only dumps the assets when a asset is changed when it runs)
		$this->output->writeln('  > assetic dump');
		$logFile = $this->processManager->getLogFile('assetic-dump');
		$process = ProcessUtils::getAssetic(false, $this->env, $this->output->getVerbosity());
		$process->run();
		file_put_contents($logFile, $process->getOutput() . "\n" . $process->getErrorOutput());
	}

	public function cleanProjects() {
		$that = $this;
		foreach ($this->compassProjectRoots as $bundleName => $compassProjectRoot)
		{
			$this->output->writeln(sprintf('  > compass clean: <info>%s</info>', $bundleName));

			$compass = ProcessUtils::getCompass($compassProjectRoot, $this->output->getVerbosity());
			$compass->run(function ($type, $buffer) use(&$that) {
				$that->writeCompassOutput($buffer);
			});
		}
	}

	public function cleanLogs()
	{
		$this->output->writeln('Cleaning logs and temporary files...');

		$finder = new Finder();
		$logs = $finder
			->in($this->processManager->getRunDir())
			->name('*.log')
			->files();

		foreach($logs as $logFile) {
			unlink($logFile);
		}

		if(file_exists($this->bundleMapFile)) {
			unlink($this->bundleMapFile);
		}
		if(file_exists($this->bundlePublicMapFile)) {
			unlink($this->bundlePublicMapFile);
		}
	}

	/**
	 * Used to normalize and format indentation of compass output. Cosmetic stuff only!
	 * @param $output
	 */
	private function writeCompassOutput($output) {
		// to check if the line is empty, the bash color codes need to be removed
		// we don't change the buffer itself so we can output the colors too later on if the line is really not empty
		// http://unix.stackexchange.com/questions/55546/removing-color-codes-from-output
		if(strlen(trim(preg_replace('/\x1B\[[0-9;]*[JKmsu]/', '', $output))) === 0) {
			return;
		}

		// remove the whitespace before the actions executed by compass
		// so far there is remove/unchanged/create. because of the bash color codes before the actions and whitespace,
		// simply using trim() doesn't work. this is solely cosmetic and doesn't serve any higher purpose :D
		foreach(explode("\n", $output) as $line) {
			$this->output->writeln(sprintf('    %s', preg_replace('/\s+(remove|unchanged|create)/', '\1', $line)));
		}
	}
}