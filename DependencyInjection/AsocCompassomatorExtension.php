<?php

namespace Asoc\CompassomatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AsocCompassomatorExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('asoc_compassomator.bundle_finder.config_rb_dir', $config['config_rb_dir']);
        $container->setParameter('asoc_compassomator.bundle_finder.config_rb_name', $config['config_rb_name']);
        $container->setParameter('asoc_compassomator.bundle_finder.bundles_dir', $config['bundles_public_dir']);
        $container->setParameter('asoc_compassomator.bundle_finder.assetic_css_root', $config['assetic_css_dir']);
        $container->setParameter('asoc_compassomator.process_runner.manage_assetic', $config['manage_assetic']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
