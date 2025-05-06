<?php
/**
 * Dashboard sync page template
 *
 * @var array                           $member
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls
 * @var string                          $type
 * @var array                           $membership_data
 * @var int                             $hub_site_id
 *
 * @package WPMUDEV Dashboard 4.9.0
 */

$profile = $member['profile'];

// The Hub.
$hub   = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub-connected.png';
$hub2x = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub-connected@2x.png';

// WordPress.
$wordpress   = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress-connected.png';
$wordpress2x = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress-connected@2x.png';

$installed_free_projects = array();
$upgrade_hash            = wp_create_nonce( 'project-upgrade-free' );
$redirect_hash           = wp_create_nonce( 'login-success' );
$free_projects           = WPMUDEV_Dashboard::$site->get_installed_free_projects();

if ( 'single' === $type ) {
	// todo: multiple plugins single type in the future.
	foreach ( $free_projects as $free_project ) {
		if ( absint( $free_project['id'] ) === absint( $membership_data['membership'] ) ) {
			$installed_free_projects[] = $free_project;
		}
	}
} elseif ( 'full' === $type ) {
	$installed_free_projects = $free_projects;
} elseif ( 'unit' === $type ) {
	foreach ( $free_projects as $free_project ) {
		if ( in_array( absint( $free_project['id'] ), $membership_data['membership_projects'] ) ) {
			$installed_free_projects[] = $free_project;
		}
	}
}
$installed_free_projects_names_concat = '';
if ( ! empty( $installed_free_projects ) && in_array( $type, array( 'expired', 'paused', 'free' ), true ) ) {
	// Build plugin names.
	$installed_free_projects_names        = wp_list_pluck( $installed_free_projects, 'name' );
	$installed_free_projects_names_concat = array_pop( $installed_free_projects_names );
	if ( $installed_free_projects_names ) {
		$installed_free_projects_names_concat = implode( ', ', $installed_free_projects_names ) . ' &amp; ' . $installed_free_projects_names_concat;
	}
}

$show_success = isset( $_GET['show'] ) && 'success' === $_GET['show'] ? true : false;
// Current membership type.
$membership_type = WPMUDEV_Dashboard::$api->get_membership_status();

$is_hosted_third_party = WPMUDEV_Dashboard::$api->is_hosted_third_party();

$hub_site_url = $urls->hub_url;
if ( ! empty( $hub_site_id ) ) {
	$hub_site_url = trailingslashit( $hub_site_url ) . "site/$hub_site_id/overview/quick-setup";
}

?>


<div class="dashui-onboarding">
	<div class="dashui-onboarding-body dashui-onboarding-content-center">
		<div class="dashui-login-form">
			<div class="dashui-connect">
				<div class="dashui-connect-header" aria-hidden="true">
					<div class="dashui-connect-image">
						<img
							src="<?php echo esc_url( $hub ); ?>"
							srcset="<?php echo esc_url( $hub ); ?> 1x, <?php echo esc_url( $hub2x ); ?> 2x"
							class="sui-image"
							alt=""
						/>
					</div>
					<div class="dashui-connect-ready-bar"></div>
					<div class="dashui-connect-image">
						<img
							src="<?php echo esc_url( $wordpress ); ?>"
							srcset="<?php echo esc_url( $wordpress ); ?> 1x, <?php echo esc_url( $wordpress2x ); ?> 2x"
							class="sui-image"
							alt=""
						/>
					</div>
				</div>
			</div>

			<?php if ( $show_success || empty( $installed_free_projects ) ) : // Backward compat. ?>
				<h2><?php esc_html_e( 'Connected to The Hub', 'wpmudev' ); ?></h2>
				<span class="sui-description">
					<?php
					if ( 'free' === $membership_type || $is_hosted_third_party ) {
						esc_html_e( 'Your site was successfully connected. You can now configure site services, manage updates, and so much more directly from The Hub.', 'wpmudev' );
					} else {
						printf(
							esc_html__( 'Your site was successfully connected. Pro plugins are unlocked and you are synced to The Hub. What do you want to do next, %1$s?', 'wpmudev' ),
							esc_html( $profile['name'] )
						);
					}
					?>
				</span>

				<div class="dashui-connect">
					<div class="dashui-connect-actions">
						<?php if ( 'free' === $membership_type || $is_hosted_third_party ) : ?>
							<a
								class="sui-button sui-button-ghost sui-button-lg"
								href="<?php echo esc_url( $urls->dashboard_url ); ?>"
								role="button"
							>
								<span class="sui-icon-wpmudev-logo" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Go To Dashboard', 'wpmudev' ); ?></span>
							</a>
						<?php else : ?>
							<a
								class="sui-button sui-button-ghost sui-button-lg"
								href="<?php echo esc_url( $urls->plugins_url ); ?>"
								role="button"
							>
								<span class="sui-icon-plugin-2" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Install Plugins', 'wpmudev' ); ?></span>
							</a>
						<?php endif; ?>
						<a
							class="sui-button sui-button-blue sui-button-lg"
							href="<?php echo esc_url( $hub_site_url ); ?>"
							target="_blank"
							role="button"
						>
							<span class="sui-icon-hub" aria-hidden="true"></span>
							<?php esc_html_e( 'View site in the Hub', 'wpmudev' ); ?>
						</a>
					</div>
				</div>

			<?php else : ?>
				<div class="js-upgrade-process">
					<h2><?php esc_html_e( 'Connected, upgrading...', 'wpmudev' ); ?></h2>
					<span class="sui-description">
						<?php
						esc_html_e(
							'We’ve detected you have free versions of plugins installed and are automatically upgrading them to pro. Don’t worry, you won’t lose any settings.',
							'wpmudev'
						);
						?>
					</span>

					<div class="dashui-connect js-sync-plugins">
						<ul class="dashui-connect-process js-sync-plugin-list">
							<li class="dashui-ready"><?php esc_html_e( 'Successfully connected to The Hub', 'wpmudev' ); ?></li>
							<?php foreach ( $installed_free_projects as $project ) : ?>
								<li
									class="js-upgrading"
									data-project="<?php echo esc_attr( $project['id'] ); ?>"
									data-hash="<?php echo esc_attr( $upgrade_hash ); ?>"
									data-redirecth="<?php echo esc_attr( $redirect_hash ); ?>"
								>
									<?php printf( esc_html__( 'Upgrading %1$s', 'wpmudev' ), esc_html( $project['name'] ) ); ?>
								</li>
								<li
									class="dashui-ready sui-hidden js-upgraded"
									data-project="<?php echo esc_attr( $project['id'] ); ?>"
								>
									<?php printf( esc_html__( 'Upgrading %1$s', 'wpmudev' ), esc_html( $project['name'] ) ); ?>
								</li>
								<li
									class="dashui-failed sui-hidden js-failed-upgrading"
									data-project="<?php echo esc_attr( $project['id'] ); ?>"
								>
									<?php printf( esc_html__( 'Upgrading %1$s Failed', 'wpmudev' ), esc_html( $project['name'] ) ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>

				<div class="js-upgrade-success sui-hidden">
					<h2><?php esc_html_e( 'All done.', 'wpmudev' ); ?></h2>
					<span class="sui-description">
						<?php
						printf(
							esc_html__(
								'You now have active pro versions of %1$s. Plus you’re synced The Hub which means you can manage all your websites in one place.%2$sWhat do you want to do next, %3$s ?',
								'wpmudev'
							),
							esc_html( $installed_free_projects_names_concat ),
							'<br/>',
							esc_html( $profile['name'] )
						);
						?>
					</span>
					<div class="dashui-connect">
						<div class="dashui-connect-actions">
							<a href="<?php echo esc_url( $urls->plugins_url ); ?>" role="button">
								<i class="sui-icon-plugin-2" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Install Plugins', 'wpmudev' ); ?></span>
							</a>
							<a
								href="<?php echo esc_url( $urls->hub_url ); ?>"
								target="_blank"
								role="button">
								<i class="sui-icon-hub" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Go to The Hub', 'wpmudev' ); ?></span>
							</a>
						</div>
					</div>
				</div>

				<div class="js-upgrade-failed sui-hidden">
					<span class="sui-description">
						<?php
						printf(
							esc_html__( 'You’re synced to The Hub but we\'ve failed to upgrade free plugins. What do you want to do next, %1$s ?', 'wpmudev' ),
							esc_html( $profile['name'] )
						);
						?>
					</span>
					<div class="dashui-connect">

						<div class="dashui-connect-actions">
							<a
								href="<?php echo esc_url( add_query_arg( 'view', 'sync-plugins', $urls->dashboard_url ) ); ?>"
								role="button"
							>
								<i class="sui-icon-update" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Retry', 'wpmudev' ); ?></span>
							</a>
							<a href="<?php echo esc_url( $urls->plugins_url ); ?>" role="button">
								<i class="sui-icon-plugin-2" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Install Plugins', 'wpmudev' ); ?></span>
							</a>
							<a
								href="<?php echo esc_url( $urls->hub_url ); ?>"
								target="_blank"
								role="button">
								<i class="sui-icon-hub" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Go to The Hub', 'wpmudev' ); ?></span>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>