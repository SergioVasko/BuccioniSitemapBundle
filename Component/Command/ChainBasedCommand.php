<?php

namespace Buccioni\Bundle\SitemapBundle\Component\Command;

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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class ChainBasedCommand extends ContainerAwareCommand
{
    protected $chain;
    protected $sitemap;

    const action        = 'nothing';
    const commandPrefix = 'buccioni:sitemap:';

    abstract public function command(InputInterface $input, OutputInterface $output);

    protected function commandName() {
        $this->setName(self::commandPrefix.static::action);
        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('<comment>* Starting sitemap '.static::action.' process</comment>', true);
        list($smsec, $ssec) = explode(' ', microtime());

        $this->sitemap = $this->getContainer()->get('buccioni_sitemap');
        $this->chain = $this->getContainer()->get('buccioni_sitemap.provider.chain');

        $this->command($input, $output);

        list($emsec, $esec) = explode(' ', microtime());
        $endTime = number_format((($esec - $ssec) + ($emsec-$smsec)), 3, '.', '');
        $output->write('<comment>* Sitemap was sucessfully '.static::action.'d in '.$endTime.' secs </comment>', true);
    }
}