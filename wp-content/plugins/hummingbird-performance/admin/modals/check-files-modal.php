<?php
/**
 * Asset optimization: checking files modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-lg">
	<div role="dialog" class="sui-modal-content" id="check-files-modal" aria-modal="true" aria-labelledby="checkingFiles" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="checkingFiles">
					<?php esc_html_e( 'Checking files', 'wphb' ); ?>
				</h3>
				<div class="sui-actions-right">
					<small class="sui-no-margin-bottom"><?php esc_html_e( 'File check in progress...', 'wphb' ); ?></small>
				</div>
			</div>

			<div class="sui-box-body">
				<p id="dialogDescription">
					<?php esc_html_e( 'Hummingbird is running a file check to see what files can be optimized.', 'wphb' ); ?>
				</p>

				<div class="sui-progress-block">
					<div class="sui-progress">
						<span class="sui-progress-icon" aria-hidden="true">
							<span class="sui-icon-loader sui-loading"></span>
						</span>
						<div class="sui-progress-text">
							<span>0%</span>
						</div>
						<div class="sui-progress-bar" aria-hidden="true">
							<span style="width: 0"></span>
						</div>
					</div>
					<button class="sui-button-icon sui-tooltip" id="cancel-minification-check" onclick="WPHB_Admin.minification.scanner.cancel()" type="button" data-modal-close="" data-tooltip="<?php esc_attr_e( 'Cancel Test', 'wphb' ); ?>">
						<span class="sui-icon-close" aria-hidden="true"></span>
					</button>
				</div>

				<div class="sui-progress-state sui-margin-bottom">
					<span class="sui-progress-state-text"><?php esc_html_e( 'Looking for files...', 'wphb' ); ?></span>
				</div>

				<?php if ( ! \Hummingbird\Core\Utils::is_member() ) : ?>
					<?php
					$this->admin_notices->show_inline(
						esc_html__( 'Did you know the Pro version of Hummingbird comes up to 2x better compression and a CDN to store your assets on? Get it as part of a WPMU DEV membership.', 'wphb' ),
						'info',
						sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
							esc_html__( '%1$sLearn more%2$s', 'wphb' ),
							'<a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'plugin' ) ) . '" target="_blank">',
							'</a>'
						)
					);
					?>
				<?php endif; ?>

				<?php $cdn_status = \Hummingbird\Core\Utils::get_module( 'minify' )->get_cdn_status(); ?>
				<?php if ( ! is_multisite() && \Hummingbird\Core\Utils::is_member() ) : ?>
					<form method="post" id="enable-cdn-form">
						<div class="sui-border-frame">
							<label for="enable_cdn" class="sui-toggle">
								<input type="checkbox" name="enable_cdn" id="enable_cdn" aria-labelledby="enable_cdn-label" aria-describedby="enable_cdn-description" <?php checked( $cdn_status ); ?>>
								<span class="sui-toggle-slider" aria-hidden="true"></span>
								<span id="enable_cdn-label" class="sui-toggle-label">
									<?php esc_html_e( 'Store my files on the WPMU DEV CDN', 'wphb' ); ?>
								</span>
								<span id="enable_cdn-description" class="sui-description">
									<?php esc_html_e( 'By default your files are hosted on your own server. With this pro setting enabled we will host your files on WPMU DEV’s secure and hyper fast CDN.', 'wphb' ); ?>
								</span>
								<span class="sui-description sui-toggle-description">
									<?php esc_html_e( 'Note: Some externally hosted files can cause issues when added to the CDN. You can exclude these files from being hosted in the Settings tab.', 'wphb' ); ?>
								</span>
							</label>
						</div>
					</form>
				<?php elseif ( is_multisite() && \Hummingbird\Core\Utils::is_member() ) : ?>
					<input type="checkbox" aria-hidden="true" name="enable_cdn" id="enable_cdn" <?php checked( $cdn_status ); ?> style="display: none" hidden>
				<?php endif; ?>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<div class="sui-box-footer sui-content-center sui-flatten sui-spacing-bottom--0">
					<img class="sui-image sui-no-margin-bottom" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-minify-summary.png' ); ?>"
						srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-minify-summary.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-minify-summary@2x.png' ); ?> 2x">
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script type="text/javascript">
		jQuery('label[for="enable_cdn"]').on('click', function(e) {
			e.preventDefault();
			var checkbox = jQuery('input[name="enable_cdn"]');
			checkbox.prop('checked', !checkbox.prop('checked') );
		});
	</script>
</div>
