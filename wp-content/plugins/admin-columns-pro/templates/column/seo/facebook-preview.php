<?php

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @var string $image_url
 * @var string $url
 * @var string $title
 * @var string $description
 */

?>

<div class="acu-rounded acu-border acu-bg-[#eee] acu-max-w-[500px] acu-bg-red">
    <?php
    if ($this->image_url): ?>
		<img src="<?= esc_attr($this->image_url) ?>"
			class="acu-aspect-[1.91/1] acu-max-w-full acu-object-cover acu-block">
    <?php
    endif; ?>
	<div class="acu-p-2 acu-break-words acu-text-wrap">
		<div class="acu-uppercase"><small><?= $this->url ?></small></div>
		<div><strong><?= $this->title ?></strong></div>
		<div><?= $this->description ?></div>
	</div>
</div>