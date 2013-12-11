<?php

namespace Asoc\CompassomatorBundle\Command;

use Asoc\CompassomatorBundle\CacheClearer\CacheClearer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends ContainerAwareCommand
{
    /**
     * @var CacheClearer
     */
    private $cacheClearer;

    protected function configure()
    {
        $this->setName('compassomator:clean')
            ->setDescription('Run compass clean on all compass projects and remove logs.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container          = $this->getContainer();
        $this->cacheClearer = $container->get('asoc_compassomator.cache_clearer');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cacheClearer->run($output);
    }
}
