<?php
/**
 * The upsell modal content.
 *
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div class="<?php echo esc_attr( WPMUDEV_Dashboard::$sui_version ); ?>">
	<div class="wpmudev-dashboard-upsell-wrap">
		<div class="sui-modal sui-modal-lg">
			<div
				role="dialog"
				id="wpmudev-dashboard-upsell"
				class="sui-modal-content sui-content-fade-out"
				aria-modal="true"
				aria-labelledby="wpmudev-dashboard-upsell-title"
			>
				<div class="sui-box">
					<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 sui-spacing-sides--60">
						<figure class="sui-box-banner" aria-hidden="true">
							<img
								src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upsell/upsell-expired.png' ); ?>"
								srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upsell/upsell-expired.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upsell/upsell-expired@2x.png' ); ?> 2x"
								alt="<?php esc_html_e( 'Highlights', 'wpmudev' ); ?>"
								aria-hidden="true"
							/>
						</figure>

						<button
							class="sui-button-icon sui-button-white sui-button-float--right"
							id="wpmudev-dashboard-upsell-notice-dismiss"
							data-modal-close
						>
							<span class="sui-icon-close sui-md" aria-hidden="true"></span>
							<span class="sui-screen-reader-text">
							<?php esc_html_e( 'Close modal', 'wpmudev' ); ?>
						</span>
						</button>

						<h3 id="wpmudev-dashboard-upsell-title" class="sui-box-title sui-lg">
							<?php esc_html_e( 'Unlock these WPMU DEV Pro tools for Free', 'wpmudev' ); ?>
						</h3>
						<p id="upgrade-highlights-desc1" class="sui-description">
							<?php _e( 'WPMU DEV’s brand new plan comes built-in with some of our most popular and powerful PRO features. <strong>Now 100% free for everyone</strong>.', 'wpmudev' ); // phpcs:ignore ?>
						</p>
					</div>

					<div class="sui-box-body sui-spacing-sides--60">
						<ul class="dashui-modal-list">
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'The Hub - effortlessly manage unlimited sites from one dashboard', 'wpmudev' ); ?></span>
							</li>
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Uptime monitor - instant downtime alerts and helpful site analytics', 'wpmudev' ); ?></span>
							</li>
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'White label reports - custom website health reports for clients', 'wpmudev' ); ?></span>
							</li>
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Client billing - a full payment solution for your business', 'wpmudev' ); ?></span>
							</li>
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Auto updates - schedule safe updates for all your plugins and themes', 'wpmudev' ); ?></span>
							</li>
							<li>
								<span class="sui-icon-check" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Secure site backups - including 1GB free WPMU DEV storage', 'wpmudev' ); ?></span>
							</li>
						</ul>
					</div>

					<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-bottom--50 sui-spacing-sides--60">
						<div>
							<a
								class="sui-button sui-button-blue"
								target="_blank"
								href="https://wpmudev.com/hub2/?switch-free=1&utm_source=wpmudev-dashboard&utm_medium=referral&utm_campaign=all_pages_switch-free"
							>
								<?php esc_html_e( 'Try the free plan', 'wpmudev' ); ?>
							</a>
							<p class="dashui-modal-footer-desc">
								<small><?php esc_attr_e( 'No credit card required.', 'wpmudev' ); ?></small>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>