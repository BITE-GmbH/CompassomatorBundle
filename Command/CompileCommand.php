<?php

namespace Asoc\CompassomatorBundle\Command;

use Asoc\CompassomatorBundle\Compassomator\ProcessRunner;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends ContainerAwareCommand
{

	/**
	 * @var ProcessRunner
	 */
	private $processRunner;

	protected function configure()
    {
        $this->setName('compassomator:compile')
            ->setDescription('Compass compile and assetic dump for all projects')
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
		$this->processRunner->compileProjects();
	}

}
