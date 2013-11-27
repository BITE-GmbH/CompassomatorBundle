<?php

namespace Asoc\CompassomatorBundle\Command;

use Asoc\CompassomatorBundle\Compassomator\ProcessRunner;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends ContainerAwareCommand
{

	/**
	 * @var ProcessRunner
	 */
	private $processRunner;

	protected function configure()
    {
        $this->setName('compassomator:watch')
            ->setDescription('Start a compass watch for the bundles and assetic watch')
	        ->addOption('abort', 'a', InputOption::VALUE_NONE, 'Stop it')
        ;
    }

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$container = $this->getContainer();
		$this->processRunner = $container->get('asoc_compassomator.process_runner');
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->processRunner->prepare($output);

		// stopping any previous processes just to be clean
		$this->processRunner->abortWatchProjects();

		// if abort was not actually requested, run the watch
		if($input->getOption('abort') !== true) {
			$this->processRunner->watchProjects();
		}
	}

}
