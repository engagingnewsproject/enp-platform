<?php
/**
 * Upgrade highlight modal.
 *
 * @since 2.6.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-md">
	<div
			role="dialog"
			id="upgrade-summary-modal"
			class="sui-modal-content"
			aria-modal="true"
			aria-labelledby="upgrade-summary-modal-title"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
					<figure class="sui-box-banner" aria-hidden="true">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/upgrade-summary-bg.png' ); ?>" alt=""
							srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/upgrade-summary-bg.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/upgrade-summary-bg@2x.png' ); ?> 2x">
					</figure>
				<?php endif; ?>

				<button class="sui-button-icon sui-button-float--right" data-modal-close=""
						onclick="window.WPHB_Admin.dashboard.hideUpgradeSummary()">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
				</button>

				<h3 id="upgrade-summary-modal-title" class="sui-box-title sui-lg" style="white-space: inherit">
					<?php esc_html_e( 'New: Async and Preload Optimization', 'wphb' ); ?>
				</h3>
			</div>

			<div class="sui-box-body sui-spacing-top--20 sui-spacing-bottom--20">
				<div class="wphb-upgrade-feature">
					<h6 class="wphb-upgrade-item"><?php esc_html_e( 'Asynchronous Loading', 'wphb' ); ?></h6>
					<p class="wphb-upgrade-item-desc">
						<?php esc_html_e( 'Is a third-party script increasing your page load time? Use the Async attribute to optimize how third-party scripts are loaded in Hummingbird’s manual Asset Optimization mode.', 'wphb' ); ?>
					</p>
				</div>
				<div class="wphb-upgrade-feature">
					<h6 class="wphb-upgrade-item"><?php esc_html_e( 'Preloading', 'wphb' ); ?></h6>
					<p class="wphb-upgrade-item-desc">
						<?php esc_html_e( 'You can now speed up the loading process by preloading certain resources ahead of time. Use the Preload attribute to optimize how selected assets are loaded in Hummingbird’s manual Asset Optimization mode.', 'wphb' ); ?>
					</p>
				</div>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">
				<button role="button" class="sui-button" onclick="window.WPHB_Admin.dashboard.hideUpgradeSummary()">
					<?php esc_html_e( 'Got it', 'wphb' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
