<?php
/**
 * Template to display notice to switch to free account.
 *
 * @var string                          $campaign utm_campaign value.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls URLs class.
 *
 * @since   4.11.8
 * @package WPMUDEV_Dashboard
 */

// Dynamically generate url.
$link = trailingslashit( $urls->hub_url ) . '?switch-free=1&utm_source=wpmudev-dashboard&utm_medium=referral&utm_campaign=' . esc_html( $campaign ) . '_switch-free';

?>

<div class="dashui-switch-free-notice">
	<div class="dashui-switch-free-notice__content">
		<p>
			<?php esc_attr_e( 'Switch to a Free WPMU DEV account and continue managing all your sites in your Hub.', 'wpmudev' ); ?>
			<a
				target="_blank"
				href="<?php echo esc_url( $link ); ?>"
				class="dashui-switch-free-notice__content-btn sui-button sui-button-blue"
			>
				<?php esc_attr_e( 'Switch to Free now', 'wpmudev' ); ?>
			</a>
		</p>
	</div>
</div>