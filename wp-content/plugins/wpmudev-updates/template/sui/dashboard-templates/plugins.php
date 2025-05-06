<?php
/**
 * Installed/Update available box on dashboard home
 *
 * @var string                          $type                  Membership type.
 * @var array                           $membership_data       Membership data.
 * @var array                           $selected_plugins      Plugins.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                  URLs class.
 * @var int                             $update_plugins        Updates count.
 * @var array                           $free_plugins          Installed free plugins.
 * @var array                           $data                  Data.
 * @var bool                            $has_hosted_access     Has hosted access?.
 * @var bool                            $is_hosted_third_party Is third party site.
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

// Find the 5 most popular plugins, that are not installed yet.
$selected_plugins = array();
asort( $data['projects'] );
$is_free  = 'free' === $type;
$projects = wp_list_pluck( $data['projects'], 'id', 'name' );

// sort by name.
ksort( $projects );
if ( $update_plugins > 0 && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ) :
	foreach ( $projects as $key => $item ) {
		// if update is complete break.
		if ( $update_plugins <= count( $selected_plugins ) ) {
			break;
		}

		// Skip themes.
		if ( 'plugin' !== $data['projects'][ $item ]['type'] ) {
			continue;
		}

		// No need to render addons.
		if ( ! empty( $data['projects'][ $item ]['is_plugin_addon'] ) ) {
			continue;
		}

		$wpmu_plugin = WPMUDEV_Dashboard::$site->get_project_info( $item );
		// get the updates first.
		if ( ! $wpmu_plugin->has_update ) {
			continue;
		}

		$selected_plugins[] = $wpmu_plugin->pid;
	}
endif;

foreach ( $projects as $key => $item ) {
	// Skip themes.
	if ( 'plugin' !== $data['projects'][ $item ]['type'] ) {
		continue;
	}

	// No need to render addons.
	if ( ! empty( $data['projects'][ $item ]['is_plugin_addon'] ) ) {
		continue;
	}

	$wpmu_plugin = WPMUDEV_Dashboard::$site->get_project_info( $item );

	// if update is complete break.
	if ( ! $is_free && ! $is_hosted_third_party && 5 <= count( $selected_plugins ) ) {
		break;
	}

	if (
		// ignore plugin with updates.
		( $wpmu_plugin->has_update && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ) ||
		// Skip plugin if it's not installed.
		! $wpmu_plugin->is_active ||
		// Skip plugins that are not compatible with current site.
		! $wpmu_plugin->is_compatible ||
		// Skip hidden/deprecated projects.
		$wpmu_plugin->is_hidden
	) {
		continue;
	}

	$selected_plugins[] = $wpmu_plugin->pid;
}

// Plugins add section for Free Hub.
$plugins_install_url = $urls->hub_url . '/plugins/add/wpOrg/';

?>
<div class="sui-box">
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<i class="sui-icon-plugin-2" aria-hidden="true"></i>
			<?php esc_html_e( 'Plugins', 'wpmudev' ); ?>
		</h2>
	</div>
	<div class="sui-box-body">
		<p>
			<?php if ( $is_free && ! $has_hosted_access ) : ?>
				<?php esc_html_e( 'Here are all your active WPMU DEV plugins.', 'wpmudev' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Install, update and configure our Pro plugins.', 'wpmudev' ); ?>
			<?php endif; ?>
		</p>
	</div>
	<table class="sui-table dashui-table-tools dashui-table-installed-plugins">
		<tbody>
		<?php
		foreach ( $selected_plugins as $item ) {
			$wp_plugin = WPMUDEV_Dashboard::$site->get_project_info( $item );
			?>
			<tr class="<?php echo $wp_plugin->has_update && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ? esc_attr( 'has-update' ) : ''; ?>">
				<td class="dashui-item-image">
					<?php
					echo $wp_plugin->has_update && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ? '<span class="dashui-update-dot"></span>' : '';
					$config_url = $wp_plugin->has_update ? $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' : $wp_plugin->url->config;
					?>
					<a href="<?php echo esc_url( $config_url ); ?>"><img src="<?php echo esc_url( empty( $wp_plugin->url->icon ) ? $wp_plugin->url->thumbnail_square : $wp_plugin->url->icon ); ?>" class="sui-image plugin-image" style="width:30px;height:30px;"></a>
				</td>
				<td class="dashui-item-content">
					<h4>
						<?php if ( $wp_plugin->has_update && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ) { ?>
							<a href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>">
								<a href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>">
									<?php echo esc_html( $wp_plugin->name ); ?>
								</a>
								<a href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>" style="margin-left: 10px;">
										<span class="sui-tag sui-tag-sm sui-tag-warning" style="cursor: pointer;">
										<?php
										/* translators: Plugin latest version */
										printf( esc_html__( 'v%s update available', 'wpmudev' ), esc_html( $wp_plugin->version_latest ) );
										?>
										</span>
								</a>
							</a>
						<?php } else { ?>
							<a href="<?php echo esc_url( $wp_plugin->url->config ); ?>">
								<?php echo esc_html( $wp_plugin->name ); ?>
							</a>
							<?php $version_url = $is_free || $is_hosted_third_party ? $wp_plugin->url->config : $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog'; ?>
							<a href="<?php echo esc_url( $version_url ); ?>" style="margin-left: 10px;">
									<span class="sui-tag sui-tag-sm" style="cursor: pointer;">
									<?php
									/* translators: Plugin version */
									printf( esc_html__( 'v%s', 'wpmudev' ), esc_html( $wp_plugin->version_installed ) );
									?>
									</span>
							</a>
							<?php
						}
						?>
					</h4>
				</td>
				<td>
					<?php
					$url_upgrade    = $urls->remote_site . 'hub/account/';
					$reactivate_url = add_query_arg(
						array(
							'utm_source'   => 'wpmudev-dashboard',
							'utm_medium'   => 'plugin',
							'utm_campaign' => 'dashboard_expired_modal_reactivate',
						),
						$url_upgrade
					);
					?>
					<?php if ( $wp_plugin->has_update && ( ( ! $is_free && ! $is_hosted_third_party ) || $has_hosted_access ) ) : ?>
						<?php if ( in_array( $type, array( 'expired', 'paused' ), true ) ) : ?>
							<a
								href="<?php echo esc_attr( $reactivate_url ); ?>"
								class="sui-button-icon main-action-free sui-tooltip sui-tooltip-constrained sui-tooltip-top-right"
								<?php // translators: %s name of the plugin. ?>
								data-tooltip="<?php echo sprintf( esc_html__( 'Reactivate your membership to update %s and unlock pro features', 'wpmudev' ), esc_attr( $wp_plugin->name ) ); ?>"
							>
								<i class="sui-icon-download"></i>
							</a>
						<?php else : ?>
							<a class="sui-button-icon dashui-update-from-dash" href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>">
								<i class="sui-icon-download main-icon" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
					<?php elseif ( ! empty( $wp_plugin->url->config ) ) : ?>
						<a class="sui-button-icon" href="<?php echo esc_url( $wp_plugin->url->config ); ?>">
							<i class="sui-icon-wrench-tool" aria-hidden="true"></i>
						</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}
		?>
		<?php if ( $is_free || $is_hosted_third_party ) : ?>
			<?php foreach ( $free_plugins as $file => $item ) : ?>
				<?php if ( $item['is_active'] ) : ?>
					<tr>
						<td class="dashui-item-image">
							<a href="<?php echo esc_url( admin_url( $item['wp_config_url'] ) ); ?>">
								<img
									alt="<?php echo esc_html( $item['name'] ); ?>"
									src="<?php echo esc_url( $item['icon'] ); ?>"
									class="sui-image plugin-image"
									style="width:30px;height:30px;"
								/>
							</a>
						</td>
						<td class="dashui-item-content">
							<h4>
								<a href="<?php echo esc_url( admin_url( $item['wp_config_url'] ) ); ?>">
									<?php echo esc_html( $item['name'] ); ?>
								</a>
								<span class="sui-tag sui-tag-sm">
							<?php
							/* translators: Plugin version */
							printf( esc_html__( 'v%s', 'wpmudev' ), esc_html( $item['version'] ) );
							?>
						</span>
							</h4>
						</td>
						<td>
							<?php if ( ! empty( $item['wp_config_url'] ) ) : ?>
								<a class="sui-button-icon" href="<?php echo esc_url( $item['wp_config_url'] ); ?>">
									<i class="sui-icon-wrench-tool" aria-hidden="true"></i>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<div class="sui-box-footer">
		<?php if ( ( 'free' === $type || $is_hosted_third_party ) && ! $has_hosted_access ) : ?>
			<a href="<?php echo esc_url( $plugins_install_url ); ?>" target="_blank" class="sui-button sui-button-blue">
				<span class="sui-icon-hub" aria-hidden="true"></span>
				<?php esc_html_e( 'Install Plugins', 'wpmudev' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $urls->plugins_url ); ?>" class="sui-button sui-button-ghost">
				<i class="sui-icon-eye" aria-hidden="true"></i>
				<?php esc_html_e( 'View All', 'wpmudev' ); ?>
			</a>
			<div class="sui-actions-right">
				<a href="<?php echo esc_url( $urls->plugins_url ); ?>" class="sui-button sui-button-blue">
					<span class="sui-icon-plus" aria-hidden="true"></span>
					<?php esc_html_e( 'Add Plugins', 'wpmudev' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>