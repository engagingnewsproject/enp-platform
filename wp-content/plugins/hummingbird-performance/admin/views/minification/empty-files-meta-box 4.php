<?php
/**
 * Asset optimization empty meta box.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<img class="sui-image" aria-hidden="true" alt=""
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-reports-disabled@1x.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-reports-disabled@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-reports-disabled@2x.png' ); ?> 2x" />
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php
		printf(
			/* translators: %s: username */
			esc_html__( "Hummingbird's Asset Optimization engine can combine and minify the files your website outputs when a user visits your website. The fewer requests your visitors have to make to your server, the better. Let's check to see what we can optimise, %s!", 'wphb' ),
			esc_attr( Utils::get_current_user_name() )
		);
		?>
	</p>

	<button role="button" class="sui-button sui-button-blue" id="check-files" onclick="WPHB_Admin.Tracking.enableFeature( 'Asset Optimization' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</button>
</div>

<?php $this->modal( 'check-files' ); ?>

<?php if ( Utils::get_module( 'minify' )->scanner->is_scanning() ) : ?>
	<script>
		jQuery(document).ready( function() {
			window.WPHB_Admin.getModule( 'minification' );
			jQuery( document ).trigger( 'check-files' );
		} );
	</script>
<?php endif; ?>
