<?php
/**
 * The data settings template.
 *
 * @since   4.11.4
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label sui-dark">
			<?php esc_html_e( 'Reset Settings', 'wpmudev' ); ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'Needing to start fresh? Use this button to roll back to the default settings.', 'wpmudev' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<button
				role="button"
				id="wpmudev-reset-settings-button"
				class="sui-button sui-button-ghost"
				data-modal-open="wpmudev-reset-settings-modal"
				data-modal-open-focus="wpmudev-reset-settings-close-button"
				data-modal-close-focus="wpmudev-reset-settings-button"
				data-modal-mask="true"
				data-esc-close="true"
			>
				<span class="sui-icon-undo" aria-hidden="true"></span>
				<?php esc_html_e( 'Reset Settings', 'wpmudev' ); ?>
			</button>
			<span class="sui-description">
				<?php esc_html_e( 'Note: This will instantly revert all settings to their default states but will leave your data intact.', 'wpmudev' ); ?>
			</span>
		</div>
	</div>
</div>