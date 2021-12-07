<?php
/**
 * Database cleanup modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="wphb-database-cleanup-modal" aria-modal="true" aria-labelledby="databaseCleanup">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" id="dialog-close-div" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg" id="databaseCleanup">
					<?php esc_html_e( 'Are you sure?', 'wphb' ); ?>
				</h3>

				<p class="sui-description">
					<?php esc_html_e( 'Are you sure you wish to delete 0 database entries? Make sure you have a current backup just in case.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center">
				<button class="sui-button sui-button-ghost" data-modal-close="">
					<?php esc_html_e( 'Cancel', 'wphb' ); ?>
				</button>
				<button class="sui-button sui-button-ghost sui-button-red" onclick="WPHB_Admin.advanced.confirmDelete( jQuery(this).attr('data-type') )" type="button">
					<span class="sui-icon-trash" aria-hidden="true"></span>
					<?php esc_html_e( 'Delete entries', 'wphb' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
