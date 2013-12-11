<?php

namespace Asoc\CompassomatorBundle\CacheWarmer;

use Asoc\CompassomatorBundle\Compassomator\ProcessRunner;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CacheWarmer implements CacheWarmerInterface
{
    /**
     * @var \Asoc\CompassomatorBundle\Compassomator\ProcessRunner
     */
    private $processRunner;

    public function __construct(ProcessRunner $processRunner)
    {
        $this->processRunner = $processRunner;
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * Optional warmers can be ignored on certain conditions.
     *
     * A warmer should return true if the cache can be
     * generated incrementally and on-demand.
     *
     * @return Boolean true if the warmer is optional, false otherwise
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        $this->run(new NullOutput());
    }

    public function run(OutputInterface $output)
    {
        $this->processRunner->prepare($output);
        $this->processRunner->compileProjects();
    }
}