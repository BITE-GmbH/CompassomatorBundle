<?php

namespace Asoc\CompassomatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('asoc_compassomator');

        $rootNode
            ->children()
                ->scalarNode('config_rb_dir')->defaultValue('Resources')->end()
                ->scalarNode('config_rb_name')->defaultValue('config.rb')->cannotBeEmpty()->end()
                ->scalarNode('bundles_public_dir')->defaultValue('%kernel.root_dir%/../web/bundles')->cannotBeEmpty()->end()
                // the directory where the css/js/image files that go through assetic using the twig helper functions
                // is actually hard coded in \Symfony\Bundle\AsseticBundle\Factory\Loader\AsseticHelperFormulaLoader
                // but well, for the sake of configurability :D
                ->scalarNode('assetic_css_dir')->defaultValue('%assetic.write_to%/css')->cannotBeEmpty()->end()
                ->scalarNode('manage_assetic')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
