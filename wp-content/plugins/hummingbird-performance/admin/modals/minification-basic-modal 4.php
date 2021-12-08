<?php
/**
 * Asset optimization: switch to basic mode modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="wphb-basic-minification-modal" aria-modal="true" aria-labelledby="switchBasic" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" id="dialog-close-div" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg" id="switchBasic" style="white-space: inherit">
					<?php esc_html_e( 'Preset configurations will be applied', 'wphb' ); ?>
				</h3>

				<p class="sui-description" id="dialogDescription">
					<?php
					if ( 'speedy' === \Hummingbird\Core\Settings::get_setting( 'type', 'minify' ) ) {
						printf( /* translators: %1$s - <strong>, %2$s - </strong> */
							esc_html__( 'The automatic %1$sSpeedy%2$s preset rules will be applied which will auto-compress and auto-combine your assets. This mode will inherit configurations from the current manual mode.', 'wphb' ),
							'<strong>',
							'</strong>'
						);
					} else {
						printf( /* translators: %1$s - <strong>, %2$s - </strong> */
							esc_html__( 'The automatic %1$sBasic%2$s preset rules will be applied which will auto-compress your assets. This mode will inherit configurations from the current manual mode.', 'wphb' ),
							'<strong>',
							'</strong>'
						);
					}
					?>
				</p>

				<p class="sui-description">
					<?php
					printf( /* translators: %1$s - <strong>, %2$s - </strong> */
						esc_html__( 'Configurations that are unique to the manual mode (defer/inline etc.) will be discarded with this change and %1$swon’t be saved%2$s if you decide to switch back to manual.', 'wphb' ),
						'<strong>',
						'</strong>'
					);
					?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center">
				<div class="sui-form-field">
					<label for="hide-basic-modal" class="sui-checkbox sui-checkbox-sm">
						<input type="checkbox" id="hide-basic-modal" aria-labelledby="hide-basic-label"/>
						<span aria-hidden="true"></span>
						<span id="hide-basic-label" class="sui-toggle-label">
							<?php esc_html_e( "Don't show me this again", 'wphb' ); ?>
						</span>
					</label>
				</div>

				<button class="sui-button" onclick="WPHB_Admin.minification.switchView( 'basic' )" id="wphb-switch-to-basic">
					<?php esc_html_e( 'Got it', 'wphb' ); ?>
				</button>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<img class="sui-image" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?>"
					srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@2x.png' ); ?> 2x">
			<?php endif; ?>
		</div>
	</div>
</div>
