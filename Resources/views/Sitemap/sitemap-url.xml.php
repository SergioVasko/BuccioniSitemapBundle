<sitemap>
	<loc><?= $this->absolutize($url->getLoc()) ?></loc>
	<?php if($url->getLastmod()): ?>
	<lastmod><?= $url->getLastmod()->format("c") ?></lastmod>
	<?php endif; ?>
	<?php if($url->getChangefreq()): ?>
	<changefreq><?= $url->getChangefreq() ?></changefreq>
	<?php endif; ?>
	<?php if($url->getPriority()): ?>
	<priority><?= $url->getPriority() ?></priority>
	<?php endif; ?>
	<?php foreach($url->getImages() as $image): ?>
	<image:image>
	    <?php include "BuccioniSitemapBundle:Sitemap:image.xml.php" ?>
	</image:image>
	<?php endforeach; ?>

</sitemap>
