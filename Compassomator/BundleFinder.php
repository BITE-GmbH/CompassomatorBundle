<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleFinder
{
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;
    /**
     * Subdirectory inside the bundle root where the config.rb should be searched for
     *
     * eg. Resources = the config file would be searched in src/Acme/DemoBundle/Resources
     *
     * @var string
     */
    private $configRbDir;
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;
    private $bundlesDir;
    private $asseticCssRoot;
    private $configRbName;

    public function __construct(
        KernelInterface $kernel,
        Filesystem $filesystem,
        $configRbDir,
        $configRbName,
        $bundlesDir,
        $asseticCssRoot
    ) {

        $this->kernel         = $kernel;
        $this->filesystem     = $filesystem;
        $this->configRbDir    = $configRbDir;
        $this->configRbName   = $configRbName;
        $this->bundlesDir     = rtrim($bundlesDir, '/');
        $this->asseticCssRoot = $asseticCssRoot;
    }

    /**
     * @param $bundleRoot
     *
     * @return string
     */
    public function getCompassProjectRootForBundle($bundleRoot)
    {
        return sprintf('%s/%s', $bundleRoot, $this->configRbDir);
    }

    /**
     * @param $bundleRoot
     *
     * @return string
     */
    public function getCompassConfigPathForBundle($bundleRoot)
    {
        return sprintf('%s/config.rb', $this->getCompassProjectRootForBundle($bundleRoot));
    }

    /**
     * @return array
     */
    public function findCompassProjects()
    {
        $importPaths = $this->findImportPaths();

        // TODO make the compassConfig directory and file name configurable
        $projectRoots = array();
        foreach ($importPaths as $bundleName => $bundleRoot) {
            $compassConfig = $this->getCompassConfigPathForBundle($bundleRoot);

            if (!file_exists($compassConfig)) {
                continue;
            }

            $projectRoots[$bundleName] = $this->getCompassProjectRootForBundle($bundleRoot);
        }

        return $projectRoots;
    }

    /**
     * @return array
     */
    public function findImportPaths()
    {
        $importPaths = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $bundleName               = $bundle->getName();
            $importPaths[$bundleName] = $bundle->getPath();
        }

        return $importPaths;
    }

    /**
     *
     * NOTE: may fail if symlinks for $bundlesDir or $asseticCssRoot are used (doesn't matter for directories within)
     * NOTE: the path building logic for the public dir has been copied from AssetsInstallCommand from the
     *       FrameworkBundle
     *
     * @see \Symfony\Bundle\AsseticBundle\Factory\Loader\AsseticHelperFormulaLoader
     * @see \Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand
     *
     * @return array
     */
    public function findPublicBundleDirs()
    {
        $publicDirs = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $bundleName = $bundle->getName();
            $bundlePath = $bundle->getPath();

            $originDir = rtrim($bundlePath, '/').'/Resources/public';
            if (!is_dir($originDir)) {
                continue;
            }

            $absolutePublicDir = $this->bundlesDir.'/'.preg_replace('/bundle$/', '', strtolower($bundleName));

            // make the absolute path relative to the directory where assetic will put it's css files (without trailing slash)
            $publicDirs[$bundleName] = rtrim(
                $this->filesystem->makePathRelative($absolutePublicDir, $this->asseticCssRoot),
                '/'
            );
        }

        return $publicDirs;
    }
}