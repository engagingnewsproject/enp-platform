<?php
/**
 * Uptime no membership meta box.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<img class="sui-image" aria-hidden="true" alt=""
	src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@1x.png' ); ?>"
	srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@2x.png' ); ?> 2x" />

<div class="sui-message-content">
	<p>
		<?php
		esc_html_e( 'Uptime monitors your server response time and lets you know when your website is down or too slow for your visitors. Get Uptime monitoring as part of a WPMU DEV membership.', 'wphb' );
		?>
	</p>

	<a class="sui-button sui-button-purple" role="button" href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_uptime_upgrade_button' ) ); ?>" target="_blank">
		<?php esc_html_e( 'Upgrade to Pro', 'wphb' ); ?>
	</a>
</div>
