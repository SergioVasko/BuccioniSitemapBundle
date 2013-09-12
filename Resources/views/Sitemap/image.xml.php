
		<image:loc><?= $this->absolutize($image->getLoc()) ?></image:loc>
		<?php if($image->getCaption()): ?>
		<image:caption><?= $image->getCaption() ?></image:caption>
		<?php endif ?>
		<?php if($image->getGeoLocation()): ?>
		<image:geo_location><?= $image->getGeoLocation() ?></image:geo_location>
		<?php endif ?>
		<?php if($image->getTitle()): ?>
		<image:title><?= $image->getTitle() ?></image:title>
		<?php endif ?>
		<?php if($image->getLicense()): ?>
		<image:license><?= $image->getLicense() ?></image:license>
		<?php endif ?>