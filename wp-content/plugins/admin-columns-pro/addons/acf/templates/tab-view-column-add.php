<?php
/**
 * @var AC\View $this
 * @var string  $table_id
 * @var string  $field_key
 * @var string  $field_label
 * @var string  $list_screen_id
 * @var string  $title
 * @var string  $view_url
 * @var string  $add_label
 */

?>

<div class="ac-acf-view" data-table-id="<?= esc_attr($this->table_id) ?>" data-field-key="<?= esc_attr($this->field_key) ?>" data-label="<?= esc_attr($this->field_label) ?>" data-list-screen-id="<?= esc_attr($this->list_screen_id) ?>">
	<div class="ac-acf-view-left">
		<span class="ac-acf-view-name"><?= esc_html($this->title) ?></span>
		<a href="<?= esc_url($this->view_url) ?>" class="ac-acf-link ac-acf-view-link"><?= esc_html__('Open view →', 'codepress-admin-columns') ?></a>
	</div>
	<div class="ac-acf-view-actions">
		<button type="button" class="button ac-acf-add-to-view"><?= esc_html($this->add_label) ?></button>
	</div>
</div>
