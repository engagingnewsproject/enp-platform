<?php
/**
 * Disabled Page caching meta box.
 *
 * @package Hummingbird
 *
 * @var string $activate_url  Activation URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<img class="sui-image" aria-hidden="true" alt=""
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-pagecaching-disabled.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-pagecaching-disabled.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-pagecaching-disabled@2x.png' ); ?> 2x" />
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php
		esc_html_e( 'Page caching stores static HTML copies of your pages and posts. These static files are then served to visitors, reducing the processing load on the server and dramatically speeding up your page load time. It’s probably the best performance feature ever.', 'wphb' );
		?>
	</p>

	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" type="button" onclick="WPHB_Admin.Tracking.enableFeature( 'Page Caching' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</a>
</div>
