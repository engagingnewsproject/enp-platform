<?php
/**
 * Disabled Gravatar caching meta box.
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
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-gravatarcaching-disabled.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-gravatarcaching-disabled.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-gravatarcaching-disabled@2x.png' ); ?> 2x" />
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php esc_html_e( 'Gravatar Caching stores local copies of avatars used in comments and in your theme. You can control how often you want the cache purged depending on how your website is set up.', 'wphb' ); ?>
	</p>

	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" role="button" onclick="WPHB_Admin.Tracking.enableFeature( 'Gravatar Caching' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</a>
</div>
