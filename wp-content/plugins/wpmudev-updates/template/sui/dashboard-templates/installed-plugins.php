<?php
/**
 * Installed/Update available box on dashboard home
 *
 * @package WPMUDEV DASHBOARD 4.9.0
 */

?>
<div class="sui-box">

	<div class="sui-box-header">

		<h2 class="sui-box-title">
			<i class="sui-icon-plugin-2" aria-hidden="true"></i>
			<?php esc_html_e( 'Plugins', 'wpmudev' ); ?>
		</h2>

	</div>

	<?php // box body. ?>
	<div class="sui-box-body">
		<p><?php esc_html_e( 'Install, update and configure our Pro plugins.', 'wpmudev' ); ?></p>
	</div>

	<?php // active plugin table. ?>
	<table class="sui-table dashui-table-tools dashui-table-installed-plugins">
		<tbody>
			<?php
			foreach ( $selected_plugins as $item ) {
				$wp_plugin = WPMUDEV_Dashboard::$site->get_project_info( $item );
				?>
				<tr class="<?php echo $wp_plugin->has_update ? esc_attr( 'has-update' ) : ''; ?>">
					<td class="dashui-item-image">
						<?php
						echo $wp_plugin->has_update ? '<span class="dashui-update-dot"></span>' : '';
						$config_url = $wp_plugin->has_update ? $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' : $wp_plugin->url->config;
						?>
						<a href="<?php echo esc_url( $config_url ); ?>"><img src="<?php echo esc_url( $wp_plugin->url->thumbnail_square ); ?>" class="sui-image plugin-image" style="width:30px;height:30px;"></a>
					</td>
					<td class="dashui-item-content">
						<h4>
							<?php if ( $wp_plugin->has_update ) { ?>
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
								<a href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>" style="margin-left: 10px;">
									<span class="sui-tag sui-tag-sm" style="cursor: pointer;">
									<?php
										/* translators: Plugin version */ printf( esc_html__( 'v%s', 'wpmudev' ), esc_html( $wp_plugin->version_installed ) );
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
						$url_upgrade = $urls->remote_site . 'hub/account/';

						$reactivate_url = add_query_arg(
							array(
								'utm_source'   => 'wpmudev-dashboard',
								'utm_medium'   => 'plugin',
								'utm_campaign' => 'dashboard_expired_modal_reactivate',
							),
							$url_upgrade
						);
						if ( 'free' === $membership_data['membership'] ) {
							if ( $wp_plugin->has_update ) {
								?>
								<a
									href="<?php echo esc_attr( $reactivate_url ); ?>"
									class="sui-button-icon main-action-free sui-tooltip sui-tooltip-constrained sui-tooltip-top-right"
									<?php // translators: %s name of the plugin. ?>
									data-tooltip="<?php echo sprintf( esc_html__( 'Reactivate your membership to update %s and unlock pro features', 'wpmudev' ), esc_attr( $wp_plugin->name ) ); ?>"
								>
									<i class="sui-icon-download"></i>
								</a>

							<?php } else { ?>
								<a class="sui-button-icon" href="#">
									<i class="sui-icon-lock" aria-hidden="true"></i>
								</a>
								<?php
							}
						} else {
							if ( $wp_plugin->has_update ) {
								?>
								<a class="sui-button-icon dashui-update-from-dash" href="<?php echo esc_url( $urls->plugins_url . '#pid=' . $wp_plugin->pid . '=changelog' ); ?>">
									<i class="sui-icon-download main-icon" aria-hidden="true"></i>
								</a>

							<?php } else { ?>
								<a class="sui-button-icon" href="<?php echo esc_url( $wp_plugin->url->config ); ?>">
									<i class="sui-icon-wrench-tool" aria-hidden="true"></i>
								</a>
								<?php
							}
						}
						?>

					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<?php // box footer. ?>
	<div class="sui-box-footer">
		<?php if ( 'free' !== $membership_data['membership'] ) : ?>
			<a href="<?php echo esc_url( $urls->plugins_url ); ?>" class="sui-button sui-button-ghost">
				<i class="sui-icon-eye" aria-hidden="true"></i>
				<?php esc_html_e( 'VIEW ALL', 'wpmudev' ); ?>
			</a>
			<div class="sui-actions-right">
				<a href="<?php echo esc_url( $urls->plugins_url ); ?>" class="sui-button sui-button-blue">
					<i class="sui-icon-plus" aria-hidden="true"></i>
					<?php esc_html_e( 'ADD PLUGINS', 'wpmudev' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

</div>