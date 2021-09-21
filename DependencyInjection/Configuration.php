<?php

namespace Ip\SeoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ip_seo');
        $rootNode
            ->children()
                ->scalarNode('host')->end()
                ->scalarNode('scheme')->defaultValue('https')->end()
                ->scalarNode('assets_path')->defaultValue('/assets/ipseo')->end()
                ->scalarNode('route_prefix')->defaultValue('front')->end()
                ->variableNode('exclude_route')->end()
                ->scalarNode('sitemap_location')->defaultValue('sitemap.xml')->end()
                ->scalarNode('change_freq')->defaultValue('weekly')->end()
                ->scalarNode('priority')->defaultValue(1)->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
