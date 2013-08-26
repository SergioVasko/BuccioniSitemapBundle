<?php

namespace Buccioni\Bundle\SitemapBundle\Controller;

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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Buccioni\Bundle\SitemapBundle\Manager\Sitemap;

class SitemapController
{
    private $sitemap;
    private $request;
    private $templating;

    public function __construct(Sitemap $sitemap, EngineInterface $templating, Request $request)
    {
        $this->sitemap = $sitemap;
        $this->request = $request;
        $this->templating = $templating;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function redirect($url, $status=302) {
        return new Response(null, $status, array('Location' => $url));
    }

    public function sitemap()
    {
        return $this->redirect($this->sitemap->defaultPath());
    }

    public function sitemapIndex()
    {
        if($this->sitemap->swapSitemapFileName)
            return $this->sitemap();
        else
            return $this->redirect($this->sitemap->defaultIndexPath());
    }
}
