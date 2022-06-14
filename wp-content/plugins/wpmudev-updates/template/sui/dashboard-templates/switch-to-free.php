<?php
/**
 * Dashboard switch to free template.
 *
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls
 *
 * @package WPMUDEV Dashboard 4.11.9
 */

// Dynamically generate url.
$link = trailingslashit( $urls->hub_url ) . '?switch-free=1&utm_source=wpmudev-dashboard&utm_medium=referral&utm_campaign=dashboard_dashbord_switch-free';

?>

<div class="dashui-switch-free-box">
	<span class="dashui-switch-free-box__icon sui-icon-hub sui-lg" aria-hidden="true"></span>
	<div class="dashui-switch-free-box__content">
		<p><?php esc_attr_e( 'Switch to a Free WPMU DEV account and continue managing all your sites in your Hub with 1Gb backup storage, Automate Safe Updates, Client Reports, and a lot more.', 'wpmudev' ); ?></p>
		<a
			target="_blank"
			href="<?php echo esc_url( $link ); ?>"
			class="dashui-switch-free-box__content-btn sui-button sui-button-blue"
		>
			<?php esc_attr_e( 'Switch to Free now', 'wpmudev' ); ?>
		</a>
	</div>
</div>