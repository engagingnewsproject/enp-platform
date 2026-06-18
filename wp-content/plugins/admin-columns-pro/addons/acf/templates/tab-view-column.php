<?php
/**
 * @var AC\View $this
 * @var string  $title
 * @var string  $editor_url
 */

?>

<div class="ac-acf-view ac-acf-view--added">
	<div class="ac-acf-view-left">
		<span class="ac-acf-view-name"><?= esc_html($this->title) ?></span>
	</div>
	<div class="ac-acf-view-actions"><span class="ac-acf-view-meta"><span class="ac-acf-badge">&#10003;</span><span><?= esc_html__('Added', 'codepress-admin-columns') ?></span></span><a href="<?= esc_url($this->editor_url) ?>" class="ac-acf-link"><?= esc_html__('Edit column →', 'codepress-admin-columns') ?></a></div>
</div>
