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
 *
 * @var bool                            $is_logged_in
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls
 * @var string                          $username
 * @var string                          $reason
 * @var bool                            $auto_show
 * @var array                           $licensed_projects
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

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

$reason_text = sprintf( __( '%s, looks like you\'ve logged in with an expired membership. Renew your membership to upgrade to pro plugins, and reactivate access to Hub services and support.', 'wpmudev' ), $username );//phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment

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

?>

<div class="dashui-expired-box">
	<h2 class="dashui-expired-box__text">
		<?php ( 'single' === $reason ) ? esc_html_e( 'Membership limits', 'wpmudev' ) : esc_html_e( 'Membership expired', 'wpmudev' ); ?>
	</h2>

	<p class="sui-description dashui-expired-box__text">
		<?php echo wp_kses_post( $reason_text ); ?>
	</p>

	<a
		aria-live="polite"
		href="<?php echo esc_url( $url_refresh ); ?>"
		class="sui-button sui-button-white dashui-expired-box__refresh">
		<span class="sui-button-text-default">
			<span class="sui-icon-refresh sui-sm" aria-hidden="true"></span>
			<?php esc_html_e( 'Refresh status', 'wpmudev' ); ?>
		</span>
		<span class="sui-button-text-onload">
			<span class="sui-icon-loader sui-loading sui-sm" aria-hidden="true"></span>
			<?php esc_html_e( 'Refreshing', 'wpmudev' ); ?>
		</span>
	</a>

	<a
		target="_blank"
		href="<?php echo esc_url( $url_upgrade ); ?>"
		class="sui-button sui-button-purple dashui-expired-box__action"
	>
		<?php ( 'single' === $reason ) ? esc_html_e( 'Upgrade', 'wpmudev' ) : esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
	</a>

	<a
		href="<?php echo esc_url( $url_logout ); ?>"
		class="sui-button sui-button-ghost dashui-expired-box__action">
		<?php esc_html_e( 'Switch Account', 'wpmudev' ); ?>
	</a>
</div>
