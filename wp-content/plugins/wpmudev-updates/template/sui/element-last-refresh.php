<?php
/**
 * Dashboard popup template: Info on last update-check
 *
 * Will output a single line of text that displays the last update time and
 * a link to check again.
 *
 * Following variables are passed into the template:
 *   - (none)
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

$url_check  = add_query_arg( 'action', 'check-updates' );
$last_check = WPMUDEV_Dashboard::$settings->get( 'last_run_updates', 'general' );

if ( isset( $_GET['success-action'] ) ) { // phpcs:ignore ?>
	<div class="sui-floating-notices">

		<?php
		if ( 'check-updates' === $_GET['success-action'] ) { // phpcs:ignore
				$notice_msg = '<p>' . esc_html__( 'Data successfully updated.', 'wpmudev' ) . '</p>';
				$notice_id  = 'remote-check-success';
			?>
				<div
				role="alert"
				id="<?php echo esc_attr( $notice_id ); ?>"
				class="sui-common-notice-alert sui-notice"
				aria-live="assertive"
				data-show-dismiss="true"
				data-notice-type="success"
				data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
				>
				</div>
				<?php
		}
		?>
	</div>
	<?php
}

if ( $last_check ) { ?>

	<p class="dashui-note-refresh refresh-infos">
		<?php
		printf(
			esc_html( _x( 'We last checked for updates %1$s ago %2$sCheck again%3$s', 'Placeholders: time-ago, link-open, link-close', 'wpmudev' ) ),
			'<strong>' . esc_html( human_time_diff( $last_check ) ) . '</strong>',
			' - <a href="' . esc_url( $url_check ) . '" class="has-spinner">',
			' </a>'
		);
		?>
	</p>

<?php } else { ?>

	<div class="sui-description sui-block-content-center refresh-infos">
		<?php
		printf(
			esc_html( _x( 'We did not check for updates yet... %1$sCheck now%2$s', 'Placeholders: link-open, link-close', 'wpmudev' ) ),
			'<a href="' . esc_url( $url_check ) . '" class="has-spinner">',
			' </a>'
		);
		?>
	</div>

<?php }