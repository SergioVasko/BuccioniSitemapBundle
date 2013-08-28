<?php

namespace Buccioni\Bundle\SitemapBundle\Command;

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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buccioni\Bundle\SitemapBundle\Component\Command\ChainBasedCommand;

class UpdateCommand extends ChainBasedCommand {
    const action = 'update';

    protected function configure() {
        $this->commandName()
            ->setDescription('Update Sitemaps data');
    }

    public function command(InputInterface $input, OutputInterface $output) {
        $this->chain->update($this->sitemap);
    }
}