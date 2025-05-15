<?php
/**
 * @var string  $title
 * @var array[] $items
 */

$title = $this->title;
$items = $this->items;
?>

<div class="ac-modal-images-container">
    <?php
    foreach ($this->items as $item) : ?>
		<div class="ac-image-container acu-shadow acu-rounded-lg">
			<div class="ac-image-center acu-relative">
				<img alt="<?= esc_attr($item['alt']) ?>" src="<?= esc_url($item['img_src']) ?>"
					class="acu-block acu-w-full acu-object-cover">

				<div class="ac-image-buttons acu-absolute acu-gap-3  acu-bottom-3 acu-left-3 acu-right-3 acu-flex acu-justify-center">
					<a class="ac-image-button" target="_blank" href="<?= $item['edit_url'] ?>"><?= __('Edit') ?></a>
					<a class="ac-image-button" download href="<?= $item['img_src'] ?>"><?= __('Download') ?></a>
				</div>
			</div>
			<div class="ac-image-meta acu-p-2">
				<div class="acu-font-bold acu-mb-1"><?= $item['filename'] ?? '' ?></div>
				<div class="acu-flex acu-gap-[20px]">
					<span>
						<span class="dashicons dashicons-format-image"></span>
						<?= strtoupper($item['filetype']) ?>
					</span>
					<span><?= $item['filesize'] ?></span>
					<span><?= $item['dimensions'] ?></span>
				</div>
			</div>
		</div>
    <?php
    endforeach; ?>
</div>
