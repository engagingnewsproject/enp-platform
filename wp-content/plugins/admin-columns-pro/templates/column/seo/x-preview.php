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

<div class="acu-rounded acu-border acu-border-solid acu-border-[#c3c4c7] acu-max-w-[500px] acu-bg-red">
    <?php
    if ($this->image_url): ?>
		<img src="<?= esc_url($this->image_url) ?>"
			class="acu-aspect-[1.91/1] acu-max-w-full acu-object-cover acu-block">
    <?php
    endif; ?>
	<div class="acu-p-2 acu-break-words acu-text-wrap">
		<div class="acu-uppercase"><small><?= esc_html($this->url) ?></small></div>
		<div><strong><?= esc_html($this->title) ?></strong></div>
		<div><?= esc_html($this->description) ?></div>
	</div>
</div>