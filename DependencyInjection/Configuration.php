<?php

namespace Buccioni\Bundle\SitemapBundle\DependencyInjection;

/**
 * This file is part of the BuccioniSitemapBundle package what is based on
 * BerriartSitemapBundle package what is based on the AvalancheSitemapBundle
 *
 * (c) Bulat Shakirzyanov <avalanche123.com>
 * (c) Alberto Varela <alberto@berriart.com>
 * (c) Felipe Alcacibar <falcacibar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $rootNode = $treeBuilder->root('buccioni_sitemap');

        $rootNode
            ->children()
                ->scalarNode('template_type')->defaultValue('twig')->end()
                ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('alias')->defaultValue('sitemap')->end()
                ->scalarNode('file_name')->defaultValue('sitemap')->end()
                ->scalarNode('index_file_name')->defaultValue('sitemapindex')->end()
                ->scalarNode('dir')->defaultValue(null)->end()
                ->scalarNode('dir_server_path')->defaultValue(null)->end()
                ->scalarNode('url_limit')->defaultValue(50000)->end()
                ->scalarNode('swap_sitemap_file_name')->defaultValue(false)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
