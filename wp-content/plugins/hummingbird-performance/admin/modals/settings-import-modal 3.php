<?php
/**
 * Settings import modal.
 *
 * @since 2.6.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">

	<div
		role="dialog"
		id="settings-import-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="settings-import-modal-title"
		aria-describedby="settings-import-modal-desc"
	>

		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 sui-spacing-sides--20">

				<button class="sui-button-icon sui-button-float--right" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg">
					<?php esc_html_e( 'Import', 'wphb' ) ?>
				</h3>

				<p class="sui-description">
					<?php esc_html_e( 'This lists Hummingbird configuration in the file you are importing.', 'wphb' ) ?>
				</p>
			</div>
			<div class="sui-box-body sui-content-center" style="padding-bottom: 0px;color: #888;font-size: 13px;line-height: 25px;">	

				<div class="sui-box" style="text-align:left; padding: 20px; border: 1px solid #e6e6e6; border-radius: 5px;">
					<strong><?php esc_html_e( 'Asset Optimization/Manual', 'wphb' ); ?></strong><br>
					<?php esc_html_e( 'Import your Asset Optimization custom configuration and use them on this site.', 'wphb' ); ?>
				</div>

				<div class="sui-box">
					<span id="wphb-begin-import-btn" class="sui-button sui-button-blue" aria-live="polite">
						<!-- Default State Content -->
						<span class="sui-button-text-default"><?php esc_html_e( 'BEGIN IMPORT', 'wphb' ) ?></span>
						<!-- Loading State Content -->
						<span class="sui-button-text-onload">
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'IMPORTING', 'wphb' ) ?>
						</span>
					</span>
				</div>
				<div class="sui-box">
					<?php esc_html_e( 'Note: This will override your existing Hummingbird asset optimization configuration.', 'wphb' ) ?>
				</div>

				<figure aria-hidden="true" style="padding: 0px; margin: 0px; height: 130px; overflow: hidden;text-align: center;">
					<img style="width:60%" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary.png' ); ?>" alt="<?php esc_attr_e( 'Connect Redis', 'wphb' ); ?>"
						srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary@2x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary@2x.png' ); ?> 2x">
				</figure>	
			</div>

		</div>
	</div>

</div>


