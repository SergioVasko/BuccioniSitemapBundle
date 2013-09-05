<?php

namespace Buccioni\Bundle\SitemapBundle\Provider;

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

use Symfony\Component\Console\Output\OutputInterface;
use Buccioni\Bundle\SitemapBundle\Manager\Sitemap;

class UrlProviderChain implements UrlProviderInterface
{
    private $providers;

    public function __construct()
    {
        $this->providers = array();
    }

    public function add(UrlProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    public function generate(Sitemap $sitemap, OutputInterface $output=null)
    {
        foreach ($this->providers as $provider) {
            $output->write('<info>  Processing '.get_class($provider).'</info>', true);
            if(method_exists($provider, 'populate')){
                $output->write(
                        '  (!) <comment>Notice</comment> <info>Using deprecated'
                        .' method populate instead of generate</info>'
                        , true
                );
                $provider->populate($sitemap, $output);
            } else
                $provider->generate($sitemap, $output);
        }
    }

    public function update(Sitemap $sitemap, OutputInterface $output=null)
    {
        foreach ($this->providers as $provider) {
            $provider->update($sitemap, $output);
        }
    }
}