<?php
/**
 * Uptime no membership meta box on dashboard page.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="sui-box-settings-row sui-no-padding-bottom">
	<p><?php esc_html_e( 'Monitor your website and get notified if/when it’s inaccessible. We’ll also watch your server response time.', 'wphb' ); ?></p>
</div>

<div class="sui-box-settings-row sui-upsell-row">
	<img class="sui-image sui-upsell-image"
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-uptime.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-uptime@2x.png' ); ?> 2x"
		alt="<?php esc_attr_e( 'Try Pro for FREE', 'wphb' ); ?>">

	<div class="sui-upsell-notice">
		<p>
			<?php
			printf(
				/* translators: %1$s - new line and link, %2$s - </a> */
				esc_html__( 'Performance improvements hardly matter if your website isn’t accessible. Monitor your uptime and downtime with WPMU DEV’s Uptime Monitoring website management tool. %1$sTry Pro for FREE today!%2$s', 'wphb' ),
				'<br><a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_dash_uptime_upsell_link' ) ) . '" target="_blank">',
				'</a>'
			);
			?>
		</p>
	</div>
</div>
