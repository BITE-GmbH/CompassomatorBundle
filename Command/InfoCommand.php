<?php

namespace Asoc\CompassomatorBundle\Command;

use Asoc\CompassomatorBundle\Compassomator\BundleFinder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends ContainerAwareCommand
{
    /**
     * @var BundleFinder
     */
    private $bundleFinder;

    protected function configure()
    {
        $this->setName('compassomator:info')
            ->setDescription('Show which bundles are managed by the compassomator');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container          = $this->getContainer();
        $this->bundleFinder = $container->get('asoc_compassomator.bundle_finder');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Managed bundles...</info>');

        // find compass configs in the active bundles
        $compassProjectRoots = $this->bundleFinder->findCompassProjects();

        // priunt the logs for each compass watcher
        foreach ($compassProjectRoots as $bundleName => $compassProjectRoot) {
            $output->writeln(sprintf('   > %s', $bundleName));
        }
    }
}
