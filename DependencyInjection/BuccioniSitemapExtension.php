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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BuccioniSitemapExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // I can't have enough time to figure out how can get the kernel
        // if someone can do this thing correctly I appreciate it.
        global $kernel;
        if($kernel instanceOf \AppCache) $kernel = $kernel->getKernel();

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $rootDir    = dirname($kernel->getRootDir());
        $webRoot    = $rootDir.'/web';
        $serverDir  = '/assets/sitemap';
        $emptyDir   = is_null($config['dir']) || empty($config['dir']);

        $config['dir'] = $emptyDir
                            ? $webRoot.$serverDir
                            : ($config['dir']{0} === '/' ? null : $rootDir.'/').$config['dir']
        ;

        // @todo create a better way to resolve . .. and //// problem
        $config['dir'] = preg_replace(
            array('|^./|','|/\./|','|/+|','#/((?<=\\\\)/|[^/])+?(/\.\.)#')
            , array('','/','/','')
            , $config['dir']
        );

        if(is_null($config['dir_server_path'])) {
            if($emptyDir)
                $config['dir_server_path'] = $serverDir;
            elseif($webRoot === substr($config['dir'], 0, $wrl=strlen($webRoot)))
                $config['dir_server_path'] = substr($config['dir'], $wrl);
            else
                new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException(
                        'The config parameter "dir_server_path" is not defined, and is impossibly'
                        .' to figure out where are in the default application web root'
                );
        }

        unset($webRoot);
        unset($serverDir);

        $container->setParameter('buccioni_sitemap.config.base_url', $config['base_url']);
        $container->setParameter('buccioni_sitemap.config.url_limit', $config['url_limit']);
        $container->setParameter('buccioni_sitemap.config.file_name', $config['file_name']);
        $container->setParameter('buccioni_sitemap.config.index_file_name', $config['index_file_name']);
        $container->setParameter('buccioni_sitemap.config.alias', $config['alias']);
        $container->setParameter('buccioni_sitemap.config.dir', $config['dir']);
        $container->setParameter('buccioni_sitemap.config.dir_server_path', $config['dir_server_path']);
        $container->setParameter('buccioni_sitemap.config.swap_sitemap_file_name', $config['swap_sitemap_file_name']);
    }
}
