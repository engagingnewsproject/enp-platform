<?php
/**
 * Asset optimization: upgrade modal.
 *
 * @package Hummingbird
 *
 * @since 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="wphb-upgrade-minification-modal" aria-modal="true" aria-labelledby="switchAdvanced" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<h3 class="sui-box-title sui-lg" id="switchAdvanced">
					<?php esc_html_e( 'Migrate your settings', 'wphb' ); ?>
				</h3>

				<p class="sui-description" id="dialogDescription">
					<?php esc_html_e( "Asset Optimization now has a new automatic mode which can handle optimizing your assets for you! Since you've already got an existing configuration, do you want to keep your setup or switch to the new automatic option?", 'wphb' ); ?>
				</p>

				<p class="sui-description">
					<?php esc_html_e( 'Note: Switching to automatic optimization will wipe your existing configuration.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center">
				<button class="sui-button sui-button-blue sui-no-margin-right" id="wphb-switch-to-auto" onclick="WPHB_Admin.minification.doUpgrade()">
					<?php esc_html_e( 'Switch to auto optimization', 'wphb' ); ?>
				</button>

				<a href="#" onclick="WPHB_Admin.minification.skipUpgrade()" id="wphb-keep-current-settings" data-modal-close="">
					<?php esc_html_e( 'Keep my current settings', 'wphb' ); ?>
				</a>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<img class="sui-image sui-image-center" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary.png' ); ?>"
					srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary@2x.png' ); ?> 2x">
			<?php endif; ?>
		</div>
	</div>
</div>
