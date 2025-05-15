<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id = $this->id ?: uniqid( 'ac_' );
$value = $this->value ?: '1';

?>

<div class="ac-toggle-v2<?= $this->container_class ? ' ' . $this->container_class : '' ?>" <?= $this->container_attributes; ?>>
	<span class="ac-toggle-v2__toggle">
		<input name="<?= $this->name; ?>" value="<?= esc_attr( $this->unchecked_value ); ?>" type="hidden">
		<input class="ac-toggle-v2__toggle__input" <?= $this->attributes; ?> type="checkbox" value="<?= $value; ?>" <?php if ( $this->checked ): ?>checked="checked"<?php endif; ?>>
		<span class="ac-toggle-v2__toggle__track"></span>
		<span class="ac-toggle-v2__toggle__thumb"></span>
	</span>
	<label for="<?= $id; ?>" class="ac-toggle-v2__label"><?= $this->label; ?></label>
</div>