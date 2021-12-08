<?php
/**
 * Modal window that is shown right after the asset optimization scan is finished.
 *
 * @since 1.9.2
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content wphb-assets-modal" id="wphb-assets-modal" aria-modal="true" aria-labelledby="assetsFound">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--40">
				<h3 class="sui-box-title sui-lg" id="assetsFound">
					<?php
					/* translators: %s - number of assets */
					printf( esc_html__( '%s assets found', 'wphb' ), 0 );
					?>
				</h3>

				<p class="sui-description">
					<?php esc_html_e( 'Next, optimize your file structure by turning on compression, and moving files in order to speed up your page load times.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body">
				<?php
				$this->admin_notices->show_inline(
					esc_html__( 'This is an advanced feature and can break themes easily. We recommend modifying each file individually and checking your frontend regularly for issues.', 'wphb' ),
					'warning'
				);
				?>

				<div class="sui-block-content-center">
					<button class="sui-button" onclick="WPHB_Admin.minification.goToSettings()">
						<?php esc_html_e( 'Got It', 'wphb' ); ?>
					</button>
				</div>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<img class="sui-image" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?>"
					srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@2x.png' ); ?> 2x">
			<?php endif; ?>
		</div>
	</div>
</div>
