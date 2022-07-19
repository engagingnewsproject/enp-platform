<?php
/**
 * The upgrade modal template.
 *
 * This modal shows the newly added features list
 * after the plugin update.
 *
 * @package WPMUDEV_Dashboard
 * @since   4.11.0
 */

defined( 'WPINC' ) || die();
?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="upgrade-highlights"
		class="sui-modal-content sui-content-fade-out"
		aria-modal="true"
		aria-labelledby="upgrade-highlights-title"
		aria-describedby="upgrade-highlights-desc1 upgrade-highlights-desc2"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<figure class="sui-box-banner" aria-hidden="true">
					<img
						src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/highlights/highlights-4.11.1.png' ); ?>"
						srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/highlights/highlights-4.11.1.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/highlights/highlights-4.11.1@2x.png' ); ?> 2x"
						alt="<?php esc_html_e( 'Highlights', 'wpmudev' ); ?>"
						aria-hidden="true"
					/>
				</figure>

				<button class="sui-button-icon sui-button-white sui-button-float--right modal-close-button" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
							<?php esc_html_e( 'Close modal', 'wpmudev' ); ?>
						</span>
				</button>

				<h3 id="upgrade-highlights-title" class="sui-box-title sui-lg">
					<?php esc_html_e( 'White-label WPMU DEV plugin titles & icons in your admin menu', 'wpmudev' ); ?>
				</h3>
				<p id="upgrade-highlights-desc1" class="sui-description">
					<?php
					printf(
					// translators: %s Link to whitelabel settings.
						__( 'Up until now, you could white-label WPMU DEV plugin titles & icons in your WordPress admin menu using our Branda plugin. We developed an easier solution so you can now do that right inside the Dashboard plugin. Check out how it works <a href="%s">here</a>.', 'wpmudev' ), // phpcs:ignore
						esc_url( $this->page_urls->whitelabel_url )
					);
					?>
				</p>
				<p id="upgrade-highlights-desc2" class="sui-description">
					<?php esc_html_e( 'You can of course still use Branda for more advanced configurations like white-labeling WordPress menu items for different user roles.', 'wpmudev' ); ?>
				</p>
				<?php wp_nonce_field( 'dismiss-highlights', 'highlight_modal_hash' ); ?>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-bottom--50">
				<button class="sui-button modal-close-button" data-modal-close>
					<?php esc_html_e( 'Got it', 'wpmudev' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
