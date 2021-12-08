<?php
/**
 * Dashboard page
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->do_meta_boxes( 'main' ); ?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_doc_link', false ) && ! get_option( 'wphb-hide-tutorials' ) ) : ?>
	<div id="wphb-dashboard-tutorials"></div>
<?php endif; ?>

<div class="sui-row">
	<div class="sui-col-lg-6">
		<?php $this->do_meta_boxes( 'box-dashboard-left' ); ?>
		<div id="wphb-dashboard-configs"></div>
	</div>
	<div class="sui-col-lg-6"><?php $this->do_meta_boxes( 'box-dashboard-right' ); ?></div>
</div>

<?php if ( ! \Hummingbird\Core\Utils::is_member() ) : ?>
	<div class="sui-row" id="sui-cross-sell-footer">
		<div><span class="sui-icon-plugin-2"></span></div>
		<h3><?php esc_html_e( 'Check out our other free wordpress.org plugins!', 'wphb' ); ?></h3>
	</div>
	<div class="sui-row sui-cross-sell-modules">
		<div class="sui-col-md-4">
			<!-- Cross-Sell Banner #1 -->
			<div aria-hidden="true" class="sui-cross-1">
				<span></span>
			</div>

			<div class="sui-box">
				<div class="sui-box-body">
					<h3><?php esc_html_e( 'Smush Image Compression and Optimization', 'wphb' ); ?></h3>
					<p><?php esc_html_e( 'Resize, optimize and compress all of your images with the incredibly powerful and award-winning, 100% free WordPress image optimizer.', 'wphb' ); ?></p>
					<a href="https://wordpress.org/plugins/wp-smushit/" class="sui-button sui-button-ghost" target="_blank">
						<?php esc_html_e( 'View features', 'wphb' ); ?>  <span aria-hidden="true" class="sui-icon-arrow-right"></span>
					</a>
				</div>
			</div>
		</div>

		<div class="sui-col-md-4">
			<!-- Cross-Sell Banner #2 -->
			<div aria-hidden="true" class="sui-cross-2">
				<span></span>
			</div>

			<div class="sui-box">
				<div class="sui-box-body">
					<h3><?php esc_html_e( 'Defender Security, Monitoring, and Hack Protection', 'wphb' ); ?></h3>
					<p><?php esc_html_e( 'Security Tweaks & Recommendations, File & Malware Scanning, Login & 404 Lockout Protection, Two-Factor Authentication & more.', 'wphb' ); ?></p>
					<a href="https://wordpress.org/plugins/defender-security/" class="sui-button sui-button-ghost" target="_blank">
						<?php esc_html_e( 'View features', 'wphb' ); ?> <span aria-hidden="true" class="sui-icon-arrow-right"></span>
					</a>
				</div>
			</div>
		</div>

		<div class="sui-col-md-4">
			<!-- Cross-Sell Banner #3 -->
			<div aria-hidden="true" class="sui-cross-3">
				<span></span>
			</div>

			<div class="sui-box">
				<div class="sui-box-body">
					<h3><?php esc_html_e( 'SmartCrawl Search Engine Optimization', 'wphb' ); ?></h3>
					<p><?php esc_html_e( 'Customize Titles & Meta Data, OpenGraph, Twitter & Pinterest Support, Auto-Keyword Linking, SEO & Readability Analysis, Sitemaps, URL Crawler & more.', 'wphb' ); ?></p>
					<a href="https://wordpress.org/plugins/smartcrawl-seo/" class="sui-button sui-button-ghost" target="_blank">
						<?php esc_html_e( 'View features', 'wphb' ); ?> <span aria-hidden="true" class="sui-icon-arrow-right"></span>
					</a>
				</div>
			</div>
		</div>
	</div>
	<div class="sui-cross-sell-bottom">
		<h3><?php esc_html_e( 'Your All-in-One WordPress Platform', 'wphb' ); ?></h3>
		<p><?php esc_html_e( 'Pretty much everything you need for developing and managing WordPress based websites, and then some.', 'wphb' ); ?></p>

		<a class="sui-button sui-button-green" href="<?php echo esc_url( Utils::get_link( 'wpmudev', 'hummingbird_footer_upgrade_button' ) ); ?>" target="_blank">
			<?php esc_html_e( 'Learn more', 'wphb' ); ?>
		</a>

		<img class="sui-image"
			src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/dev-team.png' ); ?>"
			srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/dev-team@2x.png' ); ?> 2x"
			alt="<?php esc_attr_e( 'Try pro features for free!', 'wphb' ); ?>">
	</div>
<?php endif; ?>

<?php $this->modal( 'clear-cache' ); ?>

<script>
	jQuery( document).ready( function () {
		window.WPHB_Admin.getModule( 'dashboard' );
	});
</script>
