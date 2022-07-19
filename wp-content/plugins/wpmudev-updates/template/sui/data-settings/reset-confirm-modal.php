<?php
/**
 * The settings reset modal template.
 *
 * @since   4.11.4
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div class="sui-modal sui-modal-sm">

	<div
		role="dialog"
		id="wpmudev-reset-settings-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="wpmudev-reset-settings-modal-title"
		aria-describedby="wpmudev-reset-settings-modal-desc"
	>

		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<button
					class="sui-button-icon sui-button-float--right"
					id="wpmudev-reset-settings-close-button"
					data-modal-close=""
				>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?>
					</span>
				</button>

				<h3 id="wpmudev-reset-settings-modal-title" class="sui-box-title sui-lg">
					<?php esc_html_e( 'Reset Settings', 'wpmudev' ); ?>
				</h3>

				<p id="wpmudev-reset-settings-modal-desc" class="sui-description">
					<?php esc_html_e( 'Are you sure you want to reset Dashboard’s settings back to the factory defaults?', 'wpmudev' ); ?>
				</p>

			</div>

			<div class="sui-box-body"></div>

			<div class="sui-box-footer sui-flatten sui-content-center">

				<button
					role="button"
					id="wpmudev-reset-settings-cancel-button"
					class="sui-button sui-button-ghost"
					data-modal-close=""
				>
					<?php esc_html_e( 'Cancel', 'wpmudev' ); ?>
				</button>

				<button
					role="button"
					aria-live="polite"
					id="wpmudev-reset-settings-confirm-button"
					class="sui-button sui-button-red sui-button-ghost"
					data-hash="<?php echo wp_create_nonce( 'reset-settings' ); // phpcs:ignore ?>"
				>
					<span class="sui-button-text-default">
						<span class="sui-icon-undo" aria-hidden="true"></span>
						<?php esc_html_e( 'Reset Settings', 'wpmudev' ); ?>
					</span>

					<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						<?php esc_html_e( 'Resetting', 'wpmudev' ); ?>
					</span>

				</button>

			</div>

		</div>

	</div>

</div>

