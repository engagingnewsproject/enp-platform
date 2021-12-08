<?php
/**
 * Settings meta box.
 *
 * @since 1.8.1
 * @package Hummingbird
 *
 * @var string $detection  File change detection. Accepts: 'manual', 'auto' and 'none'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'File Change Detection', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Choose how you want Hummingbird to react when we detect changes to your file structure.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field" role="radiogroup">
			<label for="automatic" class="sui-radio">
				<input type="radio" name="detection" id="automatic" value="auto" aria-labelledby="automatic-label" <?php checked( $detection, 'auto' ); ?>>
				<span aria-hidden="true"></span>
				<span id="automatic-label"><?php esc_html_e( 'Automatic', 'wphb' ); ?></span>
			</label>
			<span class="sui-description sui-radio-description">
				<?php esc_html_e( 'Set Hummingbird to automatically clear your cache instead of prompting you to do it manually.', 'wphb' ); ?>
			</span>

			<label for="manual" class="sui-radio">
				<input type="radio" name="detection" id="manual" value="manual" aria-labelledby="manual-label" <?php checked( $detection, 'manual' ); ?>>
				<span aria-hidden="true"></span>
				<span id="manual-label"><?php esc_html_e( 'Manual Notice', 'wphb' ); ?></span>
			</label>
			<span class="sui-description sui-radio-description">
				<?php esc_html_e( 'Get a global notice inside your WordPress Admin area anytime your cache needs clearing.', 'wphb' ); ?>
			</span>

			<label for="none" class="sui-radio">
				<input type="radio" name="detection" id="none" value="none" aria-labelledby="automatic-label" <?php checked( $detection, 'none' ); ?>>
				<span aria-hidden="true"></span>
				<span id="none-label"><?php esc_html_e( 'None', 'wphb' ); ?></span>
			</label>
			<span class="sui-description sui-radio-description">
				<?php esc_html_e( 'Disable warnings in your WP Admin area.', 'wphb' ); ?>
			</span>
		</div>
	</div>
</div>
