<?php
/**
 * Upgrade highlight modal.
 *
 * @since 2.6.0
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
					<?php esc_html_e( 'New: Cloudflare APO, Google Font Optimization', 'wphb' ); ?>
				</h3>
			</div>

			<div class="sui-box-body sui-spacing-top--20 sui-spacing-bottom--20">
				<div class="wphb-upgrade-feature">
					<h6 class="wphb-upgrade-item"><?php esc_html_e( 'Cloudflare APO ', 'wphb' ); ?></h6>
					<p class="wphb-upgrade-item-desc">
						<?php
						$url = add_query_arg( 'view', 'integrations', Utils::get_admin_menu_url( 'caching' ) );
						printf( /* translators: %1$s - opening a tag, %2$s - closing a tag */
							esc_html__( "Now you can enable and configure %1\$sCloudflare APO%2\$s with Hummingbird. Cloudflare APO will cache dynamic content and third-party scripts so the entire site is served from cache. This eliminates round trips between your server and the user's browser, drastically improving TTFB and other site performance metrics.", 'wphb' ),
							'<a href="' . esc_url( $url ) . '" onclick="window.WPHB_Admin.dashboard.hideUpgradeSummary()">',
							'</a>'
						);
						?>
					</p>
				</div>
				<div class="wphb-upgrade-feature">
					<h6 class="wphb-upgrade-item"><?php esc_html_e( 'Google Font Optimization', 'wphb' ); ?></h6>
					<p class="wphb-upgrade-item-desc">
						<?php
						printf( /* translators: %1$s - opening a tag, %2$s - closing a tag */
							esc_html__( "Using Google fonts on your site? Now they can be compressed, moved to the footer and inlined in Hummingbird's manual Asset Optimization mode. Give it a try %1\$shere%2\$s.", 'wphb' ),
							'<a href="' . esc_url( Utils::get_admin_menu_url( 'minification' ) ) . '" onclick="window.WPHB_Admin.dashboard.hideUpgradeSummary()">',
							'</a>'
						);
						?>
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
