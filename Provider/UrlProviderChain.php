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

    public function populate(Sitemap $sitemap)
    {
        foreach ($this->providers as $provider) {
            $provider->populate($sitemap);
        }
    }
}