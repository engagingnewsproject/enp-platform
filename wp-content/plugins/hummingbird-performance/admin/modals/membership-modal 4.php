<?php
/**
 * Membership modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-lg">
	<style>.sui-wrap .sui-listing li:before { top: 5px !important; }</style>
	<div role="dialog" class="sui-modal-content" id="wphb-upgrade-membership-modal" aria-modal="true" aria-labelledby="upgradeMembership" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header">
				<button class="sui-button-icon sui-button-float--right" data-modal-close="" id="dialog-close-div">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>

				<h3 class="sui-box-title" id="upgradeMembership">
					<?php esc_html_e( 'Upgrade to PRO', 'wphb' ); ?>
				</h3>
			</div>

			<div class="sui-box-body">
				<p id="dialogDescription">
					<?php esc_html_e( "Here's what you'll get by upgrading to Hummingbird Pro.", 'wphb' ); ?>
				</p>

				<ul class="sui-listing wphb-listing sui-margin-top">
					<li>
						<strong><?php esc_html_e( 'Automation', 'wphb' ); ?></strong>
						<p><?php esc_html_e( 'Schedule Hummingbird to run regular performance tests daily, weekly or monthly and get email reports delivered straight to your inbox.', 'wphb' ); ?></p>
					</li>
					<li>
						<strong><?php esc_html_e( 'Enhanced Asset Optimization', 'wphb' ); ?></strong>
						<p><?php esc_html_e( 'Compress your minified files up to 2x more than regular optimization and reduce your page load time even further.', 'wphb' ); ?></p>
					</li>
					<li>
						<strong><?php esc_html_e( 'WPMU DEV CDN', 'wphb' ); ?></strong>
						<p><?php esc_html_e( 'By default we minify your files via our API and send them back to your server. Pro users can host their files on WPMU DEV’s secure and hyper fast CDN, which will mean less load on your server and a fast visitor experience.', 'wphb' ); ?></p>
					</li>
					<li>
						<strong><?php esc_html_e( 'Smush Pro', 'wphb' ); ?></strong>
						<p><?php esc_html_e( 'A membership for Hummingbird Pro also gets you the award winning Smush Pro with unlimited advanced lossy compression that’ll give image heavy websites a speed boost.', 'wphb' ); ?></p>
					</li>
				</ul>

				<p class="sui-block-content-center sui-margin-top">
					<?php esc_html_e( 'Get all of this, plus heaps more as part of a WPMU DEV membership.', 'wphb' ); ?>
				</p>

				<div class="sui-block-content-center">
					<a role="button" target="_blank" class="sui-button sui-button-green" id="upgrade-to-pro-button"
						href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_footer_upgrade_button' ) ); ?>" >
						<?php esc_html_e( 'Upgrade to PRO', 'wphb' ); ?>
					</a>
				</div>
			</div>

			<img class="sui-image sui-image-center" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/dev-team.png' ); ?>"
				srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/dev-team.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/dev-team@2x.png' ); ?> 2x">
		</div>
	</div>
</div>
