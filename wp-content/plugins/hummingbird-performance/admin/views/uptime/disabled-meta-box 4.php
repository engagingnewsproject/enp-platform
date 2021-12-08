<?php
/**
 * Uptime disabled meta box.
 *
 * @package Hummingbird
 *
 * @var string      $activate_url    Activate Uptime URL.
 * @var bool|string $user            False if no user, or users name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<img class="sui-image" aria-hidden="true" alt=""
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@1x.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@2x.png' ); ?> 2x" />
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php
		esc_html_e( 'Uptime monitors your server response time and lets you know when your website is down or too slow for your visitors. Activate Uptime and make sure your website is always online.', 'wphb' );
		?>
	</p>

	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" type="button" onclick="WPHB_Admin.Tracking.enableFeature( 'Uptime' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</a>
</div>
