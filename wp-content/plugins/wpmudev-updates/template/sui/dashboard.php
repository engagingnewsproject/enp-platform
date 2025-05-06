<?php
/**
 * Dashboard home template
 *
 * @var array                           $member
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls
 * @var int|string                      $update_plugins
 * @var string                          $type
 * @var array                           $data
 * @var array                           $licensed_projects
 * @var array                           $membership_data
 * @var object|bool                     $staff_login
 * @var bool                            $analytics_enabled
 * @var bool                            $analytics_allowed
 * @var bool                            $whitelabel_allowed
 * @var array                           $whitelabel_settings
 * @var int                             $total_visits
 * @var bool                            $tickets_hidden
 * @var array                           $free_plugins
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

$this->render_sui_header(
	__( 'Dashboard', 'wpmudev' ),
	'dashboard'
);

$queue = WPMUDEV_Dashboard::$settings->get( 'notifications' );

// Is current membership type expired or paused?.
$expired_type = in_array( $type, array( 'expired', 'paused' ), true );

// If already dismissed don't show.
if ( 'expired' === $type || 'single' === $type ) {
	$this->render_upgrade_header( $type, $licensed_projects );
}

// @var WPMUDEV_Dashboard_Sui_Page_Urls $urls.
// Support & update stats.
$support_thread_url = $urls->support_url;

$support_threads = count( $member['forum']['support_threads'] );
$support_threads = $support_threads > 0 ? sprintf( '<span class="sui-tag sui-tag-sm sui-tag-branded"><a href="%s" style="color:#fff">%s</a></span>', esc_url( $support_thread_url ), absint( $support_threads ) ) : absint( $support_threads );

$update_plugins_html  = $update_plugins > 0 ? sprintf( '<span class="sui-tag sui-tag-sm sui-tag-warning"><a href="%s" style="color:#333">%s</a></span>', esc_url( $urls->plugins_url ), $update_plugins ) : $update_plugins;
$total_active_plugins = isset( $active_projects['all'] ) ? absint( $active_projects['all'] ) : 0;

$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();
$is_hosted_third_party = WPMUDEV_Dashboard::$api->is_hosted_third_party();
$has_hosted_access     = $is_wpmudev_host && ! $is_standalone_hosting && 'free' === $type;

?>
<?php if ( ( 'free' !== $type && ! $is_hosted_third_party ) || $has_hosted_access ) : ?>
	<div class="sui-box sui-summary sui-summary-sm">
		<div class="sui-summary-image-space" aria-hidden="true"></div>
		<div class="sui-summary-segment">
			<div class="sui-summary-details">
				<span class="sui-summary-large"><?php echo absint( $total_active_plugins ); ?></span>
				<span class="sui-summary-sub">
					<?php echo esc_html( _n( 'Active Pro plugin', 'Active Pro plugins', $total_active_plugins, 'wpmudev' ) ); ?>
				</span>
			</div>
		</div>
		<div class="sui-summary-segment">
			<ul class="sui-list">
				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Plugin Updates Available', 'wpmudev' ); ?> </span>
					<span class="sui-list-detail"><?php echo $update_plugins_html; //phpcs:ignore ?></span>
				</li>
				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Active Support Tickets', 'wpmudev' ); ?></span>
					<span class="sui-list-detail">
					<?php echo $support_threads; //phpcs:ignore  ?>
				</span>
				</li>
			</ul>
		</div>
	</div><!-- End Overview -->
<?php endif; ?>

	<div class="sui-row dashui-table-widgets">
		<div class="sui-col-md-6">
			<?php $this->render( 'sui/dashboard-templates/plugins', compact( 'data', 'urls', 'update_plugins', 'free_plugins', 'membership_data', 'type', 'has_hosted_access', 'is_hosted_third_party' ) ); // BOX: Installed Plugins. ?>
			<?php $this->render( 'sui/dashboard-templates/services', compact( 'urls', 'expired_type', 'membership_data' ) ); // BOX: Services. ?>
			<?php if ( ( 'free' !== $type && ! $is_hosted_third_party ) || $has_hosted_access ) : ?>
				<?php $this->render( 'sui/dashboard-templates/support', compact( 'urls', 'member', 'staff_login', 'membership_data', 'tickets_hidden', 'has_hosted_access', 'is_hosted_third_party' ) ); // BOX: Support. ?>
			<?php endif; ?>
		</div>

		<div class="sui-col-md-6">
			<?php if ( 'expired' === $type ) : ?>
				<?php $this->render( 'sui/dashboard-templates/switch-to-free', compact( 'urls' ) ); // BOX: Analytics. ?>
			<?php endif; ?>
			<?php if ( $expired_type ) : ?>
				<?php $this->render( 'sui/dashboard-templates/expired-membership-info', compact( 'urls', 'whitelabel_settings', 'analytics_enabled', 'total_visits', 'membership_data', 'type' ) ); // BOX: Expired Membership Info. ?>
			<?php endif; ?>
			<?php $this->render( 'sui/dashboard-templates/analytics', compact( 'urls', 'analytics_enabled', 'analytics_allowed', 'membership_data' ) ); // BOX: Analytics. ?>
			<?php if ( 'free' !== $type && ! $is_hosted_third_party ) : ?>
				<?php $this->render( 'sui/dashboard-templates/whitelabel', compact( 'urls', 'whitelabel_settings', 'whitelabel_allowed', 'membership_data' ) ); // BOX: Whitelabel. ?>
			<?php endif; ?>
			<?php $this->render( 'sui/dashboard-templates/resources', compact( 'urls', 'type', 'membership_data', 'has_hosted_access', 'is_hosted_third_party' ) ); // BOX: Resources. ?>
		</div>
	</div>
<?php
$this->render( 'sui/element-last-refresh', array(), true );
$this->render( 'sui/footer', array(), true );