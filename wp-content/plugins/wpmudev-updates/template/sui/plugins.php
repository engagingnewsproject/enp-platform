<?php
/**
 * Dashboard plugin template
 *
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls URLs class.
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

$free = false;
if ( 'free' === $membership_data['membership'] ) {
	$free = true;
}

// Render the page header section.
$page_title = __( 'Plugins', 'wpmudev' );
$page_slug  = 'plugins';

// plugins & update stats.
$support_thread_url   = $urls->support_url;
$update_plugins_num   = $update_plugins;
$total_active_plugins = isset( $active_projects['all'] ) ? absint( $active_projects['all'] ) : 0;
$update_plugins_html  = '';
if ( $update_plugins > 0 ) {
	$update_plugins_html = sprintf( '<span class="sui-tag sui-tag-warning sui-tag-sm"><a href="%s" style="color:#333">%s</a></span>', esc_url( $urls->plugins_url ), $update_plugins );
} else {
	$update_plugins_html = __( 'All up to date', 'wpmudev' );
}
$queue = WPMUDEV_Dashboard::$settings->get( 'notifications' );

// @var $this WPMUDEV_Dashboard_Sui */
$this->render_sui_header( $page_title, $page_slug );

if ( in_array( $type, array( 'expired', 'paused' ), true ) ) {
	$this->render_switch_free_notice( 'dashboard_plugins' );
}

$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();
$has_hosted_access     = $is_wpmudev_host && ! $is_standalone_hosting;

?>
<div class="sui-floating-notices">
	<?php
	if ( isset( $_GET['success-action'] ) ) : //phpcs:ignore
		switch ( $_GET['success-action'] ) { //phpcs:ignore
			case 'activate':
				$notice_msg = '<p>' . esc_html__( 'Plugins have been successfully activated.', 'wpmudev' ) . '</p>';
				break;
			case 'deactivate':
				$notice_msg = '<p>' . esc_html__( 'Plugins have been successfully deactivated.', 'wpmudev' ) . '</p>';
				break;
			case 'install-activate':
				$notice_msg = '<p>' . esc_html__( 'Plugins have been successfully installed and activated.', 'wpmudev' ) . '</p>';
				break;
			default:
				break;
		}
		?>
		<?php if ( isset( $notice_msg ) ) : ?>
		<div
			role="alert"
			id="notice-success-plugins"
			class="sui-plugins-notice-alert sui-notice"
			aria-live="assertive"
			data-show-dismiss="true"
			data-notice-type="success"
			data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
		>
		</div>
	<?php endif; ?>
	<?php endif; ?>
</div>

<div class="sui-box sui-summary sui-summary-sm">

	<div class="sui-summary-image-space" aria-hidden="true"></div>

	<div class="sui-summary-segment">

		<div class="sui-summary-details">
			<span class="sui-summary-large"><?php echo absint( $total_active_plugins ); ?></span>
			<span class="sui-summary-sub"><?php echo esc_html( _n( 'Active Pro plugin', 'Active Pro plugins', $total_active_plugins, 'wpmudev' ) ); ?></span>
		</div>

	</div>

	<div class="sui-summary-segment">

		<ul class="sui-list">

			<li>
				<span class="sui-list-label"><?php esc_html_e( 'Plugin Updates Available', 'wpmudev' ); ?> </span>
				<span class="sui-list-detail"><?php echo $update_plugins_html; //phpcs:ignore ?></span>
			</li>

			<li>
				<span class="sui-list-label"><?php esc_html_e( 'Total Active Plugins', 'wpmudev' ); ?></span>
				<span class="sui-list-detail">
					<?php echo $all_plugins; //phpcs:ignore  ?>
				</span>
			</li>

		</ul>

	</div>

</div><!-- End Overview -->

<div class="sui-row-with-sidenav dashui-plugin-box">
	<div class="sui-sidenav dashui-plugins-filter-tabs dashui-mobile-hidden">
		<div class="sui-sidenav-sticky sui-sidenav-hide-md">
			<div class="sui-tabs-menu sui-sidenav-sticky">
				<ul class="sui-vertical-tabs">
					<li class="sui-vertical-tab current">
						<a role="button"
						   class="sui-tab-item wdev-all-tab active"
						   data-filter="all"
						   tabindex="1">
							<?php esc_html_e( 'All', 'wpmudev' ); ?>
						</a>
					</li>

					<!-- <li class="sui-vertical-tab">
						<a role="button"
							class="sui-tab-item"
							data-filter="activated"
							tabindex="2">
							<?php esc_html_e( 'Activated', 'wpmudev' ); ?>
						</a>
					</li>

					<li class="sui-vertical-tab">
						<a role="button"
							class="sui-tab-item"
							data-filter="deactivated"
							tabindex="3">
							<?php esc_html_e( 'Deactivated', 'wpmudev' ); ?>
						</a>
					</li> -->

					<?php if ( ! empty( $update_plugins ) ) : ?>
						<li class="sui-vertical-tab">
							<a role="button"
							   class="sui-tab-item wdev-update-tab"
							   data-filter="hasupdate"
							   data-count="<?php echo esc_attr( $update_plugins ); ?>"
							   tabindex="4"
							   style="display:inline-block; position:relative; width:40%"
							>
								<?php esc_html_e( 'Updates', 'wpmudev' ); ?> <span class="sui-tag sui-tag-yellow sui-tag-sm" style="<?php echo is_rtl() ? 'right: -30px' : 'right: -25px;'; ?>"><?php echo esc_html( $update_plugins ); ?></span>
							</a>
						</li>
					<?php endif; ?>
				</ul>

			</div>

		</div>
		<div class="sui-sidenav-hide-lg" style="margin-bottom: 20px;">
			<select name="dashui-mobile-filter" class="sui-select sui-select-lg" id="dashui-mobile-filter">
				<option value="all"><?php esc_html_e( 'All', 'wpmudev' ); ?></option>
				<?php if ( ! empty( $update_plugins ) && $update_plugins ) : ?>
					<option value="hasupdate"><?php esc_html_e( 'Updates', 'wpmudev' ); ?></option>
				<?php endif; ?>
			</select>
		</div>
	</div>

	<div class="sui-box" id="dashui-all-plugins">

		<div class="sui-box-header">

			<h2 class="sui-box-title"><?php esc_html_e( 'All Plugins', 'wpmudev' ); ?></h2>

			<div class="sui-actions-right">

				<div class="sui-form-field dashui-plugins-filter-search">

					<label for="dashboard-plugins-search-field" id="dashboard-plugins-search-field-label" class="sui-screen-reader-text"></label>

					<div class="sui-control-with-icon">

						<input
							type="text"
							name="search"
							placeholder="<?php esc_html_e( 'Search plugins', 'wpmudev' ); ?>"
							id="dashboard-plugins-search-field"
							class="sui-form-control"
							aria-labelledby="dashboard-plugins-search-field-label"
						/>

						<i class="sui-icon-magnifying-glass-search" aria-hidden="true"></i>

					</div>

				</div>

			</div>

		</div><!-- end box header -->

		<div class="sui-box-body">

			<div
				role="alert"
				id="sui-no-result-search"
				class="js-no-result-search-message sui-notice"
				aria-live="assertive"
				data-show-dismiss="false"
				data-notice-type="info"
				data-notice-msg=""
			>
			</div>

			<p style="margin-top: 0;"><?php esc_html_e( 'Install, update and configure our Pro plugins.', 'wpmudev' ); ?></p>

			<?php wp_nonce_field( 'project-install', 'project-install-hash' ); ?>

			<?php if ( in_array( $type, array( 'expired', 'paused' ), true ) ) : ?>
				<div class="sui-notice sui-notice-purple">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Your WPMU DEV Membership has expired and pro versions of installed plugins have been downgraded. Reactivate your subscription to upgrade pro plugins.', 'wpmudev' ); //phpcs:ignore ?></p>
							<p>
								<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate" class="sui-button sui-button-purple">
									<?php esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?></a>
							</p>
						</div>
					</div>
				</div>
			<?php elseif ( 'free' === $type && ! $has_hosted_access ) : ?>
				<div class="sui-notice sui-notice-purple">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Upgrade your membership to unlock all the WPMU DEV Pro plugins, have full access to Hub services, 24/7 support and 3 free hosted sites.', 'wpmudev' ); //phpcs:ignore ?></p>
							<p>
								<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate" class="sui-button sui-button-purple">
									<?php esc_html_e( 'Upgrade Membership', 'wpmudev' ); ?>
								</a>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<div role="alert" class="sui-box-body dashui-plugin-loader">

			<p><?php printf( esc_html__( '%s Checking for updates, please wait…', 'wpmudev' ), '<i class="sui-icon-loader sui-md sui-loading" aria-hidden="true"></i>' ); //phpcs:ignore ?></p>

		</div>

		<table class="sui-table sui-table-flushed dashui-table-plugins" style="display: none;">

			<tbody>

			<tr class="dashui-bulk-action bulk-action-row js-plugins-bulk-action">

				<td colspan="3">
					<div class="sui-box-search">

						<label
							for="bulk-actions-all"
							class="sui-checkbox"
						>
							<input
								type="checkbox"
								name="all-actions"
								id="bulk-actions-all"
								class="js-plugin-check-all"
							/>
							<span aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all plugins', 'wpmudev' ); ?></span>
						</label>

						<select
							name="current-bulk-action"
							class="sui-select sui-select-sm sui-select-inline"
							data-width="200px"
						>
							<option value=""><?php esc_html_e( 'Bulk Actions', 'wpmudev' ); ?></option>
							<option value="update"><?php esc_html_e( 'Update', 'wpmudev' ); ?></option>
							<option value="activate"><?php esc_html_e( 'Activate', 'wpmudev' ); ?></option>
							<option value="install"><?php esc_html_e( 'Install', 'wpmudev' ); ?></option>
							<option value="install-activate"><?php esc_html_e( 'Install & Activate', 'wpmudev' ); ?></option>
							<option value="deactivate"><?php esc_html_e( 'Deactivate', 'wpmudev' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'wpmudev' ); ?></option>
						</select>

						<button
							class="sui-button sui-button-ghost js-plugins-bulk-action-button"
							disabled="disabled"
						>
							<?php esc_html_e( 'Apply', 'wpmudev' ); ?>
						</button>

					</div>

				</td>

			</tr>

			</tbody>

		</table>

		<div class="sui-box-body">

			<?php $this->render( 'sui/element-last-refresh' ); ?>

		</div>

	</div>

</div>

<?php
$this->render( 'sui/plugins-bulk-notice' );

foreach ( $data['projects'] as $project ) {
	if ( empty( $project['id'] ) ) {
		continue;
	}

	if ( 'plugin' !== $project['type'] ) {
		continue;
	}

	// No need to render addons.
	if ( ! empty( $project['is_plugin_addon'] ) ) {
		continue;
	}

	$this->render_project( $project['id'] );
}
?>

<div class="sui-hidden">
	<?php
	/**
	 * ROW FOR NOT INSTALLED PLUGIN LIST TABLE
	 */
	?>
	<div class="js-available-plugin-header">
		<table>
			<tr class="dashui-tr-header">
				<td><p><?php esc_html_e( 'Available', 'wpmudev' ); ?></p></td>
				<td></td>
				<td></td>
			</tr>
		</table>
	</div>
</div>

<?php // bulk action. ?>
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="bulk-action-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="bulk-action-modal-title"
		aria-describedby=""
	>
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 id="bulk-action-modal-title" class="sui-box-title"><?php esc_html_e( 'Bulk Actions', 'wpmudev' ); ?></h3>
				<div class="sui-actions-right" aria-hidden="true">
					<button class="sui-button-icon sui-button-float--right bulk-modal-close" data-modal-close="">
						<i class="sui-icon-close sui-md" aria-hidden="true"></i>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
					</button>
				</div>

			</div>
			<div class="sui-box-body">
				<div
					role="alert"
					id="js-bulk-errors"
					class="js-bulk-errors sui-notice sui-notice-warning"
					aria-live="assertive"
				>
				</div>
				<div
					role="alert"
					id="js-bulk-warnings"
					class="sui-notice sui-notice-yellow"
					aria-live="assertive"
				>
				</div>

				<?php
				$plugin_actions = array( 'activate', 'deactivate', 'install-activate' );
				foreach ( $plugin_actions as $plugin_action ) :
					// Message for page reload.
					$msg = '<p>' . esc_html__( 'This page needs to be reloaded before changes you just made become visible.', 'wpmudev' ) . '</p>';
					$msg        .= '<div class="sui-notice-buttons"><a href="' . esc_url( add_query_arg( 'success-action', $plugin_action, $urls->plugins_url ) ) . '" class="sui-button">' . esc_html__( 'Reload now', 'wpmudev' ) . '</a></div>';
					?>
					<div
						role="alert"
						id="js-bulk-message-need-reload-<?php echo esc_html( $plugin_action ); ?>"
						data-message='<?php echo wp_kses_post( $msg ); ?>'
						class="sui-notice js-bulk-message-need-reload"
						aria-live="assertive"
					>
					</div>
				<?php endforeach; ?>
				<div class="sui-progress-block">

					<div class="sui-progress">

						<span class="sui-progress-icon js-bulk-actions-loader-icon" aria-hidden="true">
							<i class="sui-icon-loader sui-loading"></i>
						</span>

						<span class="sui-progress-text">
							<span>0%</span>
						</span>

						<div class="sui-progress-bar" aria-hidden="true">
							<span style="width: 0%" class="js-bulk-actions-progress"></span>
						</div>
					</div>
				</div>

				<div class="sui-progress-state">
					<span class="js-bulk-actions-state"></span>
				</div>

			</div>

			<div
				class="sui-hidden js-bulk-hash"
				data-activate="<?php echo esc_attr( wp_create_nonce( 'project-activate' ) ); ?>"
				data-deactivate="<?php echo esc_attr( wp_create_nonce( 'project-deactivate' ) ); ?>"
				data-install="<?php echo esc_attr( wp_create_nonce( 'project-install' ) ); ?>"
				data-install-activate="<?php echo esc_attr( wp_create_nonce( 'project-install-activate' ) ); ?>"
				data-delete="<?php echo esc_attr( wp_create_nonce( 'project-delete' ) ); ?>"
				data-update="<?php echo esc_attr( wp_create_nonce( 'project-update' ) ); ?>"
			>
			</div>
		</div>
	</div>

</div>

<?php
$this->render( 'sui/footer' );

if ( ! WPMUDEV_Dashboard::$upgrader->can_auto_install( 'plugin' ) ) {
	$this->render( 'sui/popup-ftp-details' );
}
?>