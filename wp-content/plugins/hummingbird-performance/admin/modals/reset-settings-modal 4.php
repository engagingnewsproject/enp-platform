<?php
/**
 * Reset settings modal.
 *
 * @since 2.0.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="wphb-reset-settings-modal" aria-modal="true" aria-labelledby="resetSettings" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" id="dialog-close-div" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg" id="resetSettings">
					<?php esc_html_e( 'Reset Settings', 'wphb' ); ?>
				</h3>

				<p class="sui-description" id="dialogDescription">
					<?php esc_html_e( 'Are you sure you want to reset Hummingbird’s settings back to the factory defaults?', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body">
				<div class="sui-block-content-center">
					<button type="button" class="sui-button sui-button-ghost" data-modal-close="">
						<?php esc_html_e( 'Cancel', 'wphb' ); ?>
					</button>

					<button type="button" class="sui-button sui-button-ghost sui-button-red" onclick="WPHB_Admin.settings.confirmReset()">
						<span class="sui-icon-trash" aria-hidden="true"></span>
						<?php esc_html_e( 'Reset settings', 'wphb' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
