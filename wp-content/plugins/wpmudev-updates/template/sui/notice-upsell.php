<?php
/**
 * The upsell notice content.
 *
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div
	class="notice notice-info is-dismissible dashui-notice"
	id="wpmudev-dashboard-upsell-notice"
>
	<p><strong><?php esc_attr_e( 'Try WPMU DEV’s new FREE plan!', 'wpmudev' ); ?></strong></p>
	<p><?php esc_attr_e( 'Did you know WPMU DEV is now 100% free for unlimited sites? No credit card required, fast and easy to get started.', 'wpmudev' ); ?></p>
	<p>
		<a
			target="_blank"
			class="button button-primary dashui-upsell-button"
			href="https://wpmudev.com/hub2/?switch-free=1&utm_source=wpmudev-dashboard&utm_medium=referral&utm_campaign=all_pages_switch-free"
		><?php esc_attr_e( 'Switch to free plan', 'wpmudev' ); ?></a>
		<button
			class="button dashui-upsell-button"
			id="wpmudev-dashboard-upsell-notice-more"
		><?php esc_attr_e( 'Find out more', 'wpmudev' ); ?></button>
		<a
			href="javascript:void(0)"
			id="wpmudev-dashboard-upsell-notice-extend"
		><?php esc_attr_e( 'Remind me later', 'wpmudev' ); ?></a>
	</p>
</div>