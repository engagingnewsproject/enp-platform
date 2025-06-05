<?php
/**
 * Logout confirmation modal template.
 *
 * @since   4.11.24
 * @package WPMUDEV_Dashboard
 */

// Logout URL.
$logout_url = add_query_arg( 'clear_key', 1, WPMUDEV_Dashboard::$ui->page_urls->dashboard_url );

?>
<div
	class="sui-modal sui-modal-sm"
	id="logout-confirmation"
>
	<div
		role="dialog"
		aria-modal="true"
		class="sui-modal-content"
		id="logout-confirmation-content"
		aria-labelledby="logout-confirmation-title"
		aria-describedby="logout-confirmation-desc"
	>
		<div class="sui-box" role="document">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button
					class="sui-button-icon sui-button-float--right"
					data-modal-close=""
				>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>
				<h3
					class="sui-box-title sui-lg"
					id="logout-confirmation-title"
				>
					<?php esc_html_e( 'Logout', 'wpmudev' ); ?>
				</h3>
				<p
					class="sui-description"
					id="logout-confirmation-desc"
				>
					<?php esc_html_e( 'By logging out of the WPMU DEV Dashboard plugin, you\'ll also be disconnected from WPMU DEV free services. Don’t worry! You can easily reconnect to these services directly from their respective plugins within your site.', 'wpmudev' ); ?>
				</p>
			</div>
			<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-bottom--60">
				<button
					class="sui-button sui-button-ghost"
					data-modal-close=""
				>
					<?php esc_html_e( 'Stay Connected', 'wpmudev' ); ?>
				</button>
				<a
					class="sui-button sui-button-blue"
					href="<?php echo esc_url( $logout_url ); ?>"
				>
					<?php esc_html_e( 'Confirm Logout', 'wpmudev' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>