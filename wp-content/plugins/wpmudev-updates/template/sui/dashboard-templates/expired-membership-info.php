<?php
/**
 * Dashboard home template
 *
 * @var string                          $type Membership type.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls URLs class.
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

// Dynamically generate url.
$link = trailingslashit( $urls->hub_account_url ) . '?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate';

?>

<div class="sui-box">
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>
			<?php esc_html_e( 'WPMU DEV Membership', 'wpmudev' ); ?>
		</h2>
		<div class="sui-actions-left">
			<span class="sui-tag sui-tag-pro">
				<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
			</span>
		</div>
	</div>

	<div class="sui-box-body">
		<p>
			<?php esc_html_e( 'Your membership has expired so we\'ve locked down pro features. Renew your membership today to upgrade Pro plugins and a full suite of website management tools.', 'wpmudev' ); ?>
		</p>

		<ol class="sui-upsell-list">
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'Premium WordPress plugins', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'Smush and Hummingbird Pro performance pack', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'Complete marketing suite — pop-ups, email and more', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'Automated testing and reporting', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'White label tools', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'Manage unlimited WordPress sites from the Hub', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( '24/7 live WordPress support', 'wpmudev' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-md" aria-hidden="true"></span>
				<?php esc_html_e( 'The WPMU DEV Guarantee', 'wpmudev' ); ?>
			</li>
		</ol>
	</div>

	<div class="sui-box-footer" style="padding-top: 0; border-top: 0;">
		<a
			href="<?php echo esc_url( $link ); ?>"
			class="sui-button sui-button-purple"
		>
			<?php esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
		</a>
	</div>
</div>
