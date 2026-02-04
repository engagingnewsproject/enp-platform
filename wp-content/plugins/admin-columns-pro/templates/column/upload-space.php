<?php

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @var string $attr_class
 * @var string $storage_in_use_absolute
 * @var string $storage_max
 * @var int    $storage_in_use_percentage
 * @var bool   $has_storage_restrictions
 */

?>

<div class="ac-upload-space <?= esc_attr($this->attr_class); ?>">
	<div class="ac-upload-space-labels">
		<div class="inner">
			<span class="ac-upload-space-icon"></span>
			<span class="ac-upload-space-left"><?= $this->storage_in_use_absolute; ?></span>
            <?php
            if ($this->has_storage_restrictions) : ?>
				<span class="ac-upload-space-right"><?= $this->storage_max; ?></span>
            <?php
            endif; ?>
		</div>
	</div>

    <?php
    if ($this->has_storage_restrictions) : ?>
		<div class="ac-upload-space-progress">
			<span class="ac-upload-space-progress-bar" style="width:<?= esc_attr(
                $this->storage_in_use_percentage
            ); ?>%"></span>
		</div>
    <?php
    endif; ?>
</div>