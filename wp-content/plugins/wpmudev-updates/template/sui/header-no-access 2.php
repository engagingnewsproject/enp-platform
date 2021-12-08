<?php
/**
 * Dashboard popup template: No Access!
 *
 * This popup is displayed when a user is logged in and can view the current
 * Dashboard page, but the WPMUDEV account does not allow him to use the
 * features on the current page.
 * Usually this is displayed when a member has a single license and visits the
 * Plugins or Themes page (he cannot install new plugins or themes).
 *
 * Following variables are passed into the template:
 *   $is_logged_in
 *   $urls
 *   $username
 *   $reason
 *   $auto_show
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

/** @var  WPMUDEV_Dashboard_Sui_Page_Urls $urls */
$url_upgrade = $urls->remote_site . 'hub/account/';

$url_upgrade = add_query_arg(
	array(
		'utm_source'   => 'wpmudev-dashboard',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'dashboard_expired_modal_reactivate',
	),
	$url_upgrade
);

$url_logout  = $urls->dashboard_url . '&clear_key=1';
$url_refresh = wp_nonce_url( add_query_arg( 'action', 'check-updates' ), 'check-updates', 'hash' );

$reason_text = sprintf( __( '%s, looks like you’ve logged in with an expired membership. Renew your membership to upgrade pro plugins, and reactivate access to Hub services, support and 3 free hosted sites.', 'wpmudev' ), $username );//phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment

if ( 'single' === $reason ) {
	$reason_text = sprintf(
		// translators: 1$ - user's name, 2$/3$ - markup, 4$ - Plugin name, 5$/6$ - markup.
		esc_html__(
			'%1$s, you are on our %2$sSingle Plugin%3$s plan which gives you access to %4$s and some basic site management tools. You can %5$supgrade%6$s at any time to install more pro plugins or connect more sites.',
			'wpmudev'
		),
		$username,
		'<span style="color:#666;font-weight:500">',
		'</span>',
		$licensed_projects[0]->name,
		'<a target="_blank" href="' . esc_url( $url_upgrade ) . '" style="color:#8D00B1">',
		'</a>'
	);
}
$js_url = WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-notice.js';

?>


<!-- ELEMENT: Page Header -->
<div class="wdp-notice sui-upgrade-page-header dash-upgrade-main">

	<div class="sui-upgrade-page__container">

		<div class="sui-upgrade-page-header__content">

			<h3 class="sui-box-title sui-upgrade-title">
				<?php ( 'single' === $reason  ) ? esc_html_e( 'Membership limits', 'wpmudev' ) : esc_html_e( 'Membership expired', 'wpmudev' ); ?>
			</h3>

			<form class="wdp-notice">

				<div class="sui-box-body sui-block-content-center" style="padding: 0 30px;">
					<p id="dialogDescription">
						<?php
						// @codingStandardsIgnoreStart: Reason contains HTML, no escaping!
						echo wp_kses_post( $reason_text );
						// @codingStandardsIgnoreEnd
						?>
					</p>

					<div class="sui-block-content-center">
						<a
						href="<?php echo esc_url( $url_upgrade ); ?>"
						target="_blank"
						class="sui-button sui-button-md" target="_blank" style="background-color:#8D00B1">
							<?php ( 'single' === $reason ) ? esc_html_e( 'Upgrade', 'wpmudev' ) : esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
						</a>
					</div>
				</div>

				<div class="sui-box-footer membership-upgrade-footer" style="padding: 30px;">
					<a href="<?php echo esc_url( $url_refresh ); ?>">
						<i class="sui-icon-refresh" aria-hidden="true"></i>
						<?php esc_html_e( 'Refresh Status', 'wpmudev' ); ?>
					</a>
					<a href="<?php echo esc_url( $url_logout ); ?>">
						<i class="sui-icon-logout" aria-hidden="true"></i>
						<?php esc_html_e( 'Switch Account', 'wpmudev' ); ?>
					</a>
				</div>
				<div class="sui-block-content-center">
					<img
						src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/graphic-support-new.png' ); ?>"
						srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/graphic-dashboard-modal-upgrade.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/graphic-dashboard-modal-upgrade@2x.png' ); ?> 2x"
						alt="Upgrade"
						aria-hidden="true"
						style = "vertical-align: middle; width:40%;"
					/>
				</div>
				<input type="hidden" name="msg_id" value="<?php echo absint( $notice_id ); ?> ">
				<input type="hidden" name="force" value="1">

			</form>
		</div>
	</div>
</div>
<script src="<?php echo esc_url( $js_url ); ?>"></script> <?php //phpcs:ignore ?>
