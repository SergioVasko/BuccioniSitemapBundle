<?php
namespace Buccioni\Bundle\SitemapBundle\DependencyInjection\Compiler;

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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class UrlProviderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('buccioni_sitemap.provider.chain')) {
            return;
        }

        $definition = $container->getDefinition('buccioni_sitemap.provider.chain');

        foreach ($container->findTaggedServiceIds('buccioni_sitemap.provider') as $id => $attributes) {
            $definition->addMethodCall('add', array(new Reference($id)));
        }
    }
}