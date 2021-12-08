<?php
/**
 * Tracking modal.
 *
 * @since 2.5.0
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="tracking-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="tracking-modal-title"
		aria-describedby="tracking-modal-desc"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 sui-spacing-sides--70">
				<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
					<figure class="sui-box-banner" aria-hidden="true">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?>" alt=""
							srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup@2x.png' ); ?> 2x">
					</figure>
				<?php endif; ?>

				<button class="sui-button-icon sui-button-float--right" data-modal-close="" onclick="window.WPHB_Admin.dashboard.skipSetup()">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
				</button>

				<h3 id="tracking-modal-title" class="sui-box-title sui-lg">
					<?php
					printf(
						/* translators: $s - user name */
						esc_html__( 'Hey, %s!', 'wphb' ),
						Utils::get_current_user_name()
					);
					?>
				</h3>

				<p id="tracking-modal-desc" class="sui-description">
					<?php esc_html_e( 'Welcome to Hummingbird, the hottest Performance plugin for WordPress! We recommend running a quick performance test before you start tweaking things. Alternatively you can skip this step if you’d prefer to start customizing.', 'wphb' ); ?>
				</p>
			</div>

			<?php if ( ! is_multisite() || ( is_multisite() && is_network_admin() ) ) : ?>
				<div class="sui-box-body sui-content-center sui-spacing-top--20 sui-margin-top sui-spacing-bottom--20">
					<div class="sui-form-field">
						<label for="tracking-toggle-modal" class="sui-toggle">
							<input type="checkbox" id="tracking-toggle-modal" aria-labelledby="tracking-label-modal" aria-describedby="tracking-description-modal" onchange="window.WPHB_Admin.Tracking.toggle()"/>
							<span class="sui-toggle-slider" aria-hidden="true"></span>
							<span id="tracking-label-modal" class="sui-toggle-label">
								<?php esc_html_e( 'Allow usage data tracking', 'wphb' ); ?>
							</span>
							<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_html_e( 'Help us improve Hummingbird by letting our product designers gain insight into what features need improvement. We don’t track any personalized data, it’s all basic stuff.', 'wphb' ); ?>">
								<span class="sui-icon-info" aria-hidden="true"></span>
							</span>
						</label>
						<span id="tracking-description-modal" class="sui-description">
							<?php
							printf(
								/* translators: %1$s - <a>, %2$s - </a> */
								esc_html__( 'You can read about what data will be collected %1$shere%2$s.', 'wphb' ),
								'<a href="https://wpmudev.com/docs/privacy/our-plugins/#usage-tracking" target="_blank">',
								'</a>'
							);
							?>
						</span>
					</div>
				</div>
			<?php endif; ?>

			<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-top--30">
				<button role="button" class="sui-button sui-button-blue" onclick="window.WPHB_Admin.dashboard.runPerformanceTest()">
					<?php esc_html_e( 'Run Performance Test', 'wphb' ); ?>
				</button>
			</div>
		</div>
		<button role="button" class="sui-modal-skip" onclick="window.WPHB_Admin.dashboard.skipSetup()">
			<?php esc_html_e( 'Skip this', 'wphb' ); ?>
		</button>
	</div>
</div>
