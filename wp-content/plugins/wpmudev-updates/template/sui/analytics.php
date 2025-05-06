<?php
/**
 * Dashboard template: Analytics Functions
 *
 * @var bool                            $analytics_enabled
 * @var bool                            $analytics_allowed
 * @var string                          $analytics_role
 * @var array                           $analytics_metrics
 * @var array                           $membership_data
 * @var WPMUDEV_Dashboard_Ui            $this
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls
 * @since   4.0.0
 *
 * @package WPMUDEV_Dashboard
 */

// Render the page header section.
$this->render_sui_header(
	__( 'Analytics', 'wpmudev' ),
	'analytics'
);

// Get the upgrade link.
$url_upgrade = add_query_arg(
	array(
		'utm_source'   => 'wpmudev-dashboard',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'dashboard_analytics_activation',
	),
	$urls->remote_site . 'hub/account/'
);

?>

<?php
if ( ! $analytics_allowed ) {
	$this->render_switch_free_notice( 'analytics_plugins' );
}
?>

<?php if ( isset( $_GET['success-action'] ) ) : // phpcs:ignore ?>
	<?php
	switch ( $_GET['success-action'] ) : // phpcs:ignore
		case 'analytics-setup':
			$notice_msg = '<p>' . esc_html__( 'Analytics configuration has been saved.', 'wpmudev' ) . '</p>';
			$notice_id  = 'analytics-success';
			break;
		case 'check-updates':
			$notice_msg = '<p>' . esc_html__( 'Data successfully updated.', 'wpmudev' ) . '</p>';
			$notice_id  = 'remote-check-success';
			break;
		default:
			break;
	endswitch;
	?>
	<?php if ( isset( $notice_id, $notice_msg ) ) : ?>
		<div class="sui-floating-notices">
			<div
				role="alert"
				id="<?php echo esc_attr( $notice_id ); ?>"
				class="sui-tools-notice-alert sui-notice"
				aria-live="assertive"
				data-show-dismiss="true"
				data-notice-type="success"
				data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
			>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>

<?php if ( isset( $_GET['failed-action'] ) ) : // phpcs:ignore ?>
	<?php
	switch ( $_GET['failed-action'] ) : // phpcs:ignore
		case 'analytics-setup':
			?>
			<div class="sui-floating-notices">
				<div
					role="alert"
					id="analytics-error"
					class="sui-tools-notice-alert sui-notice"
					aria-live="assertive"
					data-show-dismiss="true"
					data-notice-type="success"
					data-notice-msg="<p><?php esc_html_e( 'Failed save analytics configuration.', 'wpmudev' ); ?></p>"
				>
				</div>
			</div>
			<?php
			break;
		default:
			break;
	endswitch;
	?>
<?php endif; ?>

	<div class="sui-row-with-sidenav">
		<div class="sui-box js-sidenav-content" id="analytics" style="display: none;">
			<form method="POST" action="<?php echo esc_url( $urls->analytics_url ); ?>">
				<input type="hidden" name="action" value="analytics-setup"/>
				<?php wp_nonce_field( 'analytics-setup', 'hash' ); ?>
				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php esc_html_e( 'Analytics', 'wpmudev' ); ?></h2>
				</div>

				<?php if ( $analytics_enabled && $analytics_allowed ) : ?>
					<?php
					$role_names = wp_roles()->get_names();
					$role_name  = isset( $role_names[ $analytics_role ] ) ? $role_names[ $analytics_role ] : 'Administrator';
					?>

					<div class="sui-box-body">

						<p><?php esc_html_e( "Add basic analytics tracking that doesn't require any third party integration, and display the data in the WordPress Admin Dashboard area.", 'wpmudev' ); ?></p>
						<div class="sui-notice sui-notice-info" style="margin-bottom:0;">
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
									<p>
										<?php
										printf(
										// translators: %s role name.
											esc_html__( 'Analytics are now being tracked and the module is being displayed to Administrators and above in their Dashboard area.', 'wpmudev' ),
											esc_html( $role_name )
										);
										?>
									</p>
								</div>
							</div>
						</div>

						<span class="sui-description" style="margin: 10px 0 30px 0;"><?php esc_html_e( 'Note: IP addresses are anonymized when stored and meet GDPR recommendations.', 'wpmudev' ); ?></span>

						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'User Role', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Choose which user roles you want to make the analytics widget available to.', 'wpmudev' ); ?></span>
							</div>
							<div class="sui-box-settings-col-2">
								<div class="sui-form-field sui-input-md">
									<select name="analytics_role" class="sui-select">
										<?php
										$roles = wp_roles()->roles;

										foreach ( $roles as $key => $site_role ) :
											?>
											<option <?php selected( $analytics_role, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $site_role['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>

						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'Metric Types', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Select the types of analytics the selected User Roles will see in their WordPress Admin area.', 'wpmudev' ); ?></span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field sui-input-md">
									<label for="analytics_metrics-pageviews" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-pageviews"
											name="analytics_metrics[]"
											value="pageviews"
											<?php checked( in_array( 'pageviews', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Page Views', 'wpmudev' ); ?></span>
									</label>
									<label for="analytics_metrics-unique_pageviews" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-unique_pageviews"
											name="analytics_metrics[]"
											value="unique_pageviews"
											<?php checked( in_array( 'unique_pageviews', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Unique Page Views', 'wpmudev' ); ?></span>
									</label>
									<label for="analytics_metrics-page_time" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-page_time"
											name="analytics_metrics[]"
											value="page_time"
											<?php checked( in_array( 'page_time', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Visit Time', 'wpmudev' ); ?></span>
									</label>
									<label for="analytics_metrics-visits" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-visits"
											name="analytics_metrics[]"
											value="visits"
											<?php checked( in_array( 'visits', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Entrances', 'wpmudev' ); ?></span>
									</label>
									<label for="analytics_metrics-bounce_rate" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-bounce_rate"
											name="analytics_metrics[]"
											value="bounce_rate"
											<?php checked( in_array( 'bounce_rate', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Bounce Rate', 'wpmudev' ); ?></span>
									</label>
									<label for="analytics_metrics-exit_rate" class="sui-checkbox sui-checkbox-stacked">
										<input
											type="checkbox"
											id="analytics_metrics-exit_rate"
											name="analytics_metrics[]"
											value="exit_rate"
											<?php checked( in_array( 'exit_rate', $analytics_metrics, true ) ); ?>
										>
										<span aria-hidden="true"></span>
										<span><?php esc_html_e( 'Exit Rate', 'wpmudev' ); ?></span>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="sui-box-footer">
						<button
							type="submit"
							name="status"
							value="deactivate"
							class="sui-button sui-button-ghost"
						>
						<span class="sui-loading-text">
							<i class="sui-icon-power-on-off" aria-hidden="true"></i>
							<?php esc_html_e( 'Deactivate', 'wpmudev' ); ?>
						</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</button>

						<div class="sui-actions-right">
							<button type="submit" class="sui-button sui-button-blue" name="status" value="settings">
							<span class="sui-loading-text">
								<i class="sui-icon-save" aria-hidden="true"></i>
								<?php esc_html_e( 'SAVE CHANGES', 'wpmudev' ); ?>
							</span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</button>
						</div>
					</div>

				<?php else : ?>

					<div class="sui-message sui-message-lg">
						<?php if ( ! $analytics_allowed ) : ?>
							<img
								src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade.png' ); ?>"
								srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade@2x.png' ); ?> 2x"
								alt="Analytics"
								class="sui-image"
								aria-hidden="true"
							/>
							<p><?php esc_html_e( 'Add basic analytics tracking that doesn\'t require any third-party integration, and display your site data in your WordPress Admin Dashboard area, and in your Hub. Upgrade your membership now to get started.', 'wpmudev' ); ?></p>
							<a href="<?php echo esc_url( $url_upgrade ); ?>" class="sui-button sui-button-purple sui-button-md" target="_blank">
								<?php esc_attr_e( 'Upgrade Membership', 'wpmudev' ); ?>
							</a>
						<?php else : ?>
							<img
								src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?>"
								srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/analytics@2x.png' ); ?> 2x"
								alt="Analytics"
								class="sui-image"
								aria-hidden="true"
							/>
							<p><?php esc_html_e( "Add basic analytics tracking that doesn't require any third party integration, and display the data in the WordPress Admin Dashboard area.", 'wpmudev' ); ?></p>
							<button
								type="submit"
								name="status"
								value="activate"
								class="sui-button sui-button-blue"
							>
								<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wpmudev' ); ?></span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</form>
		</div>
	</div>
<?php
$this->render_with_sui_wrapper( 'sui/element-last-refresh' );
$this->render_with_sui_wrapper( 'sui/footer' );