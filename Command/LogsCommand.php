<?php

namespace Asoc\CompassomatorBundle\Command;

use Asoc\CompassomatorBundle\Compassomator\BundleFinder;
use Asoc\CompassomatorBundle\Compassomator\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogsCommand extends ContainerAwareCommand
{
	/**
	 * @var ProcessManager
	 */
	private $processManager;
	/**
	 * @var BundleFinder
	 */
	private $bundleFinder;
	/**
	 * @var boolean
	 */
	private $isIncremental;

	protected function configure()
    {
        $this->setName('compassomator:logs')
            ->setDescription('Print the logs for every compass project and assetic')
            ->addOption('incremental', 'i', InputOption::VALUE_NONE, 'Wait after each printed log file before proceeding.')
        ;
    }

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$container = $this->getContainer();
		$this->processManager = $container->get('asoc_compassomator.process_manager');
		$this->bundleFinder = $container->get('asoc_compassomator.bundle_finder');
		$this->isIncremental = $input->getOption('incremental');
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// find compass configs in the active bundles
		$compassProjectRoots = $this->bundleFinder->findCompassProjects();

		// priunt the logs for each compass watcher
		foreach ($compassProjectRoots as $bundleName => $compassProjectRoot)
		{
			$name = sprintf('compass-%s', $bundleName);
			$this->printHeader($output, $bundleName);
			$this->printLogFileForProcess($output, sprintf('%s-compile', $name));
			$this->printLogFileForProcess($output, sprintf('%s-watch', $name));
		}

		// print the log for assetic
		$this->printHeader($output, 'assetic');
		$this->printLogFileForProcess($output, 'assetic-dump', true);
		$this->printLogFileForProcess($output, 'assetic-watch', true);
	}

	private function printLogFileForProcess(OutputInterface $output, $name, $last = false) {
		$logFile = $this->processManager->getLogFile($name);

		if(!file_exists($logFile)) {
			return;
		}

		$output->writeln(file_get_contents($logFile));

		if($this->isIncremental && !$last) {
			/** @var DialogHelper $dialog */
			$dialog = $this->getHelperSet()->get('dialog');
			$dialog->ask($output, 'Next?');
		}
	}

	private function printHeader(OutputInterface $output, $title) {
		$output->writeln(sprintf('<comment>%s</comment> <info>%s</info>', str_repeat('=', 60), $title));
	}
}
