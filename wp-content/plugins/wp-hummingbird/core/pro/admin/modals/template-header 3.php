<?php
/**
 * Notifications template: header.
 *
 * @since 3.1.1
 * @package Hummingbird
 *
 * @var string $back  Previous slide, for back button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<figure class="sui-box-banner" aria-hidden="true">
		<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-dash-top.png' ); ?>" alt=""
			srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-dash-top.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-dash-top@2x.png' ); ?> 2x">
	</figure>
<?php endif; ?>

<button class="sui-button-icon sui-button-float--right" onclick="location.href = wphb.links.notifications;">
	<span class="sui-icon-close sui-md" aria-hidden="true"></span>
	<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'wphb' ); ?></span>
</button>

<?php if ( isset( $back ) && $back ) : ?>
	<button class="sui-button-icon sui-button-float--left" data-modal-slide="<?php echo esc_attr( $back ); ?>" data-modal-slide-intro="back">
		<span class="sui-icon-chevron-left sui-md" aria-hidden="true"></span>
		<span class="sui-screen-reader-text"><?php esc_html_e( 'Go back', 'wphb' ); ?></span>
	</button>
<?php endif; ?>