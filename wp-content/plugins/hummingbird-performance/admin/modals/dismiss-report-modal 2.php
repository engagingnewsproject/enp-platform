<?php
/**
 * Dismiss report modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="dismiss-report-modal" aria-modal="true" aria-labelledby="dismissReport" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" id="dialog-close-div" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg" id="dismissReport">
					<?php esc_html_e( 'Are you sure?', 'wphb' ); ?>
				</h3>

				<p class="sui-description" id="dialogDescription">
					<?php esc_html_e( 'Are you sure you wish to ignore the current performance test results? You can re-run the test anytime to check your performance score again.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">
				<form method="post">
					<button class="sui-button sui-button-ghost" data-modal-close="">
						<?php esc_html_e( 'Cancel', 'wphb' ); ?>
					</button>

					<button type="submit" name="dismiss_report" id="dismiss_report" class="sui-button sui-button-blue">
						<?php esc_html_e( 'Confirm', 'wphb' ); ?>
					</button>

					<?php wp_nonce_field( 'wphb-dismiss-performance-report' ); ?>
				</form>
			</div>
		</div>
	</div>
</div>
