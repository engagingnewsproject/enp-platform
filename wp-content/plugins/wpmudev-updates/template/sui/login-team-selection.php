<?php
/**
 * Hub team selection template.
 *
 * @since   4.11.10
 * @package WPMUDEV
 */

// Team authentication.
$form_action = WPMUDEV_Dashboard::$api->rest_url( 'site-authenticate-team' );
// Redirect URL.
$redirect_url = WPMUDEV_Dashboard::$ui->page_urls->dashboard_url;
// Authenticating domain.
$domain = WPMUDEV_Dashboard::$api->network_site_url();

// nonce verifier.
$auth_verify_nonce = wp_verify_nonce( ( isset( $_REQUEST['auth_nonce'] ) ? $_REQUEST['auth_nonce'] : '' ), 'auth_nonce' );
// API key.
$api_key = empty( $_GET['key'] ) ? '' : trim( $_GET['key'] );

// Default error message.
$error = __( 'Unknown API error occurred. Please try again.', 'wpmudev' );

if ( $auth_verify_nonce && ! empty( $api_key ) ) {
	$teams = WPMUDEV_Dashboard::$api->get_user_teams( $api_key );
	// API error.
	if ( false === $teams ) {
		$error = WPMUDEV_Dashboard::$api->api_error;
	}
} else {
	// If nonce invalid or key not found redirect to login again.
	WPMUDEV_Dashboard::$ui->redirect_to( WPMUDEV_Dashboard::$ui->page_urls->dashboard_url );
}

$logo   = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/team-selection/logo.png';
$logo2x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/team-selection/logo@2x.png';
$logo3x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/team-selection/logo@3x.png';

?>

<div class="dashui-onboarding">
	<div class="dashui-onboarding-body dashui-onboarding-content-center">
		<div class="dashui-team-select-form">
			<img
				src="<?php echo esc_url( $logo ); ?>"
				srcset="<?php echo esc_url( $logo ); ?> 1x, <?php echo esc_url( $logo2x ); ?> 2x, <?php echo esc_url( $logo3x ); ?> 3x"
				class="dashui-onboarding-logo"
				alt="<?php esc_html_e( 'Select Team', 'wpmudev' ); ?>"
			/>

			<div class="dashui-team-select-header">
				<h2><?php esc_html_e( 'Choose The Hub Team', 'wpmudev' ); ?></h2>

				<span class="sui-description">
					<?php esc_html_e( 'We’ve noticed that you are a member of multiple teams in The Hub. Which team would you like to connect to this site?', 'wpmudev' ); ?>
				</span>
			</div>

			<?php if ( empty( $teams ) ) : ?>
				<div
					role="alert"
					class="sui-notice sui-notice-red sui-active"
					aria-live="assertive"
					style="display: block;"
				>
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php echo esc_html( $error ); ?></p>
						</div>
					</div>
				</div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( $form_action ); ?>">
					<input type="hidden" name="api_key" value="<?php echo esc_html( $api_key ); ?>">
					<input type="hidden" name="redirect_url" value="<?php echo esc_url( $redirect_url ); ?>">
					<input type="hidden" name="domain" value="<?php echo esc_url( $domain ); ?>">
					<input type="hidden" name="auth_nonce" value="<?php echo esc_attr( wp_create_nonce('auth_nonce') ); ?>">

					<div class="sui-box-selectors sui-box-selectors-col-1">
						<ul>
							<?php foreach ( $teams as $team ) : ?>
								<li>
									<label
										for="team-id-<?php echo intval( $team['id'] ); ?>"
										class="sui-box-selector"
									>
										<input
											type="radio"
											name="team_id"
											value="<?php echo intval( $team['id'] ); ?>"
											id="team-id-<?php echo intval( $team['id'] ); ?>"
										/>
										<span>
											<?php if ( ! empty( $team['avatar_url'] ) ) : ?>
												<span
													class="team-avatar"
													aria-hidden="true"
													style="background-image: url('<?php echo esc_url( $team['avatar_url'] ); ?>')"
												></span>
											<?php else : ?>
												<span class="sui-icon-community-people" aria-hidden="true"></span>
											<?php endif; ?>
											<?php echo esc_html( $team['nice_name'] ); ?>
										</span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
						<div class="box-footer-submit">
							<button
								type="submit"
								disabled="disabled"
								id="dashui-team-select-submit"
								class="sui-button sui-button-blue sui-button-icon-right"
							>
								<span class="sui-loading-text">
									<?php esc_html_e( 'Continue', 'wpmudev' ); ?>
								<span class="sui-icon-arrow-right" aria-hidden="true"></span>
								</span>
								<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							</button>
						</div>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>