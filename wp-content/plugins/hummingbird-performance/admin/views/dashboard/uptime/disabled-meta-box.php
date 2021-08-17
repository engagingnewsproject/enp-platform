<?php
/**
 * Uptime disabled meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $enable_url  URL to enable uptime module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="sui-margin-bottom"><?php esc_html_e( 'Monitor your website and get notified if/when it’s inaccessible. We’ll also watch your server response time.', 'wphb' ); ?></p>

<a class="sui-button sui-button-blue" href="<?php echo esc_url( $enable_url ); ?>" id="enable-uptime" onclick="WPHB_Admin.Tracking.enableFeature( 'Uptime' )">
	<?php esc_html_e( 'Activate', 'wphb' ); ?>
</a>
