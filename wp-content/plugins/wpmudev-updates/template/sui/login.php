<?php

/**
 * Following variables are passed into the template:
 *
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                    URLs class.
 * @var bool                            $connection_error        Is connection error.
 * @var bool                            $key_valid               Is key valid.
 * @var bool                            $site_limit_exceeded     Is site limit exceeded.
 * @var int                             $available_hosting_sites Available sites on current plan.
 *
 * @package wpmduev
 */

// Base URL.
$base_url = 'https://wpmudev.com/';
if ( defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && ! empty( WPMUDEV_CUSTOM_API_SERVER ) ) {
	$base_url = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
}

$register_url        = $base_url . 'register/?signup=main&utm_source=dashboard&utm_medium=plugin&utm_campaign=dashboard_connect_page';
$reset_url           = $base_url . 'wp-login.php?action=lostpassword';
$account_url         = $base_url . 'hub/account/';
$skip_trial_url      = $urls->skip_trial_url;
$hosting_url         = $base_url . 'hub2/hosting/';
$trial_info_url      = $base_url . 'docs/getting-started/how-free-trials-work/';
$websites_url        = $base_url . 'hub2/';
$security_info_url   = $base_url . 'manuals/hub-security/';
$support_url         = $base_url . 'hub/support/';
$support_modal_url   = $base_url . 'hub/support/#get-support';
$account_details_url = $base_url . 'hub2/account/details/';

$login_url = $urls->dashboard_url;
if ( ! empty( $_GET['pid'] ) ) { // phpcs:ignore
	$login_url = add_query_arg( 'pid', (int) $_GET['pid'], $login_url ); // phpcs:ignore
}

$last_user = WPMUDEV_Dashboard::$settings->get( 'auth_user', 'general' );

$login_errors = array();
if ( isset( $_GET['api_error'] ) ) { // phpcs:ignore
	$api_error  = esc_html( $_GET['api_error'] ); // phpcs:ignore
	$auth_error = isset( $_GET['auth_error'] ) ? esc_html( $_GET['auth_error'] ) : ''; // phpcs:ignore

	if ( 1 === (int) $api_error || 'auth' === $api_error ) {
		switch ( $auth_error ) {
			case 'google_linked':
				$login_errors[] = sprintf(
				// translators: %s Account detail URL.
					__( 'You are currently using your Google account as your preferred login method. If you wish to login with your WPMU DEV email & password instead, please change the <strong>Login Method</strong> in <a href="%s" target="_blank">your WPMU DEV account</a>.', 'wpmudev' ),
					$account_details_url
				);
				break;
			case 'google_unlinked':
				$login_errors[] = sprintf(
				// translators: %s Account detail URL.
					__( 'You are currently using your WPMU DEV email & password as your preferred login method. If you wish to login with your Google account instead, please change the <strong>Login Method</strong> in <a href="%s" target="_blank">your WPMU DEV account</a>.', 'wpmudev' ),
					$account_details_url
				);
				break;
			case 'reauth_google':
				$login_errors[] = sprintf(
				// translators: %1$s Account detail URL, %2$s Reset URL.
					__( 'Due to security improvements, you will need to re-link your Google account in the Hub. Please log in with your WPMU DEV email & password for now, then set up your preferred <strong>Login Method</strong> in <a href="%1$s" target="_blank">your WPMU DEV account</a>. Forgot your password? You can <a href="%2$s" target="_blank">reset it here</a>.', 'wpmudev' ),
					$account_details_url,
					$reset_url
				);
				break;
			default:
				// Invalid credentials.
				$login_errors[] = sprintf(
					'%s<br><a href="%s" target="_blank">%s</a>',
					esc_html__( 'Your login details were incorrect. Please make sure you\'re using your WPMU DEV email and password and try again.', 'wpmudev' ),
					$reset_url,
					esc_html__( 'Forgot your password?', 'wpmudev' )
				);
				break;
		}
	} else {
		switch ( $api_error ) {
			case 'in_trial':
				if ( WPMUDEV_Dashboard::$site->is_localhost() ) {
					$login_errors[] = sprintf(
						'%s<br><a href="%s" target="_blank">%s</a>',
						sprintf(
						// translators: %1$s Account name, %2$s Reset URL, %3$s Trial skip URL, %4$s Trail info URL.
							__( 'This local development site URL has previously been registered with us by the user %1$s. To use WPMU DEV with this site URL, log in with the original user (you can <a target="_blank" href="%2$s">reset your password</a>) or <a target="_blank" href="%3$s">upgrade your trial</a> to a full membership. Alternatively, try a more uniquely named development site URL. Trial accounts can\'t use previously registered domains - <a target="_blank" href="%4$s">here\'s why</a>.', 'wpmudev' ),
							'<strong style="word-break: break-all;">' . esc_html( $_GET['display_name'] ) . '</strong>', // phpcs:ignore
							$reset_url,
							$skip_trial_url,
							$trial_info_url
						),
						$support_url,
						__( 'Contact support if you need further assistance &raquo;', 'wpmudev' )
					);
				} else {
					$login_errors[] = sprintf(
						'%s<br><a href="%s" target="_blank">%s</a>',
						sprintf(
						// translators: %1$s Rest URL, %2$s Upgrade URL, %3$s Trial URL.
							__( 'This domain has previously been registered with us by the user %1$s. To use WPMU DEV on this domain, you can either log in with the original account (you can <a target="_blank" href="%2$s">reset your password</a>) or <a target="_blank" href="%3$s">upgrade your trial</a> to a full membership. Trial accounts can\'t use previously registered domains - <a target="_blank" href="%4$s">here\'s why</a>.', 'wpmudev' ),
							'<strong style="word-break: break-all;">' . esc_html( $_GET['display_name'] ) . '</strong>', // phpcs:ignore
							$reset_url,
							$skip_trial_url,
							$trial_info_url
						),
						$support_url,
						__( 'Contact support if you need further assistance &raquo;', 'wpmudev' )
					);
				}
				break;
			case 'already_registered':
				if ( WPMUDEV_Dashboard::$site->is_localhost() ) {
					$login_errors[] = sprintf(
						'%s<br><a href="%s" target="_blank">%s</a>',
						sprintf(
						// translators: %1$s Account name, %2$s Security info, %3$s Hub URL.
							__( 'This local development site URL is currently registered to %1$s. For <a target="_blank" href="%2$s">security reasons</a> they will need to go to the <a target="_blank" href="%3$s">WPMU DEV Hub</a> and remove this domain before you can log in. If that account is not yours, then make your local development site URL more unique.', 'wpmudev' ),
							'<strong style="word-break: break-all;">' . esc_html( $_GET['display_name'] ) . '</strong>', // phpcs:ignore
							$security_info_url,
							$websites_url
						),
						$support_url,
						__( 'Contact support if you need further assistance &raquo;', 'wpmudev' )
					);
				} else {
					$login_errors[] = sprintf(
					// translators: %1$d Account name, %2$s Security info, %3$s Hub URL, %4$s Support URL.
						__( 'This site is currently registered to %1$s. For <a target="_blank" href="%2$s">security reasons</a> they will need to go to the <a target="_blank" href="%3$s">WPMU DEV Hub</a> and remove this domain before you can log in. If you do not have access to that account, and have no way of contacting that user, please <a target="_blank" href="%4$s">contact support for assistance</a>.', 'wpmudev' ),
						'<strong style="word-break: break-all;">' . esc_html( $_GET['display_name'] ) . '</strong>', // phpcs:ignore.
						$security_info_url,
						$websites_url,
						$support_url
					);
				}
				break;
			case 'banned_account':
				$login_errors[] = sprintf(
				// translators: %s Support URL.
					__( 'This domain cannot be registered to your WPMU DEV account.<br><a href="%s">Contact Accounts & Billing if you need further assistance »</a>', 'wpmudev' ),
					$urls->external_support_url
				);
				break;
			case 'invalid_nonce':
			case 'invalid_double_submit_cookie':
			case 'invalid_google_creds':
			case '':
				$login_errors[] = __( 'Google login failed. Please try again.', 'wpmudev' );
				break;
			default:
				// This in case we add new error types in the future.
				$login_errors[] = __( 'Unknown error. Please update the WPMU DEV Dashboard plugin and try again.', 'wpmudev' );
				break;
		}
	}
} elseif ( $connection_error ) {
	// Variable `$connection_error` is set by the UI function `render_dashboard`.
	$login_errors[] = sprintf(
		'%s<br>%s<br><em>%s</em>',
		sprintf(
		// translators: %s error message.
			__( 'Your server had a problem connecting to WPMU DEV: "%s". Please try again.', 'wpmudev' ),
			WPMUDEV_Dashboard::$api->api_error
		),
		__( 'If this problem continues, please contact your host with this error message and ask:', 'wpmudev' ),
		sprintf(
		// translators: url to API.
			__( '"Is php on my server properly configured to be able to contact %s with a POST HTTP request via fsockopen or CURL?"', 'wpmudev' ),
			WPMUDEV_Dashboard::$api->rest_url( '' )
		)
	);
} elseif ( ! $key_valid ) {
	// Variable `$key_valod` is set by the UI function `render_dashboard`.
	$login_errors[] = __( 'Your API Key was invalid. Please try again.', 'wpmudev' );
} elseif ( $site_limit_exceeded ) {
	// Variable `$site_limit_exceeded` is set by the UI function `render_dashboard`.
	// translators: %1$d Limit, %2$s upgrade URL, %3$s Site removal URL, %4$s support URL.
	$error_msg = sprintf( __( 'You have already reached your plans limit of %1$d site, not hosted with us, connected to The Hub. <a target="_blank" href="%2$s">Upgrade your membership</a> or <a target="_blank" href="%3$s">remove a site</a> before adding another. <a target="_blank" href="%4$s">Contact support</a> for assistance.', 'wpmudev' ), $site_limit_num, $account_url, $websites_url, $support_modal_url );

	if ( $available_hosting_sites ) {
		// translators: %1$d Site count, %2$s Hosting URL.
		$error_msg .= sprintf( __( '</br><strong>Note:</strong> You still have %1$d site <a target="_blank" href="%2$s">hosted with us</a> available.', 'wpmudev' ), $available_hosting_sites, $hosting_url );
	}

	$login_errors[] = $error_msg;
}

// Get the login URL.
$form_action        = WPMUDEV_Dashboard::$api->rest_url( 'site-authenticate' );
$google_form_action = WPMUDEV_Dashboard::$api->rest_url( 'google-auth' );

// Nonce to store sso setting.
$sso_nonce = wp_create_nonce( 'sso-status' );

// Detect free plugins.
$installed_free_projects = WPMUDEV_Dashboard::$site->get_installed_free_projects();

// Build plugin names.
$installed_free_projects_names        = wp_list_pluck( $installed_free_projects, 'name' );
$installed_free_projects_names_concat = '';
$installed_free_projects_names_concat = array_pop( $installed_free_projects_names );
if ( $installed_free_projects_names ) {
	$installed_free_projects_names_concat = implode( ', ', $installed_free_projects_names ) . ' ' . '&amp;' . ' ' . $installed_free_projects_names_concat;
}

$logo   = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo.png';
$logo2x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo@2x.png';
$logo3x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo@3x.png';

?>

<div class="dashui-onboarding">
	<div class="dashui-onboarding-body dashui-onboarding-content-center">
		<div class="dashui-login-form">
			<img
				src="<?php echo esc_url( $logo ); ?>"
				srcset="<?php echo esc_url( $logo ); ?> 1x, <?php echo esc_url( $logo2x ); ?> 2x, <?php echo esc_url( $logo3x ); ?> 3x"
				class="dashui-onboarding-logo"
				alt="<?php esc_html_e( 'Login', 'wpmudev' ); ?>"
			/>

			<h2><?php esc_html_e( 'Let’s connect your site', 'wpmudev' ); ?></h2>


			<span class="sui-description"><?php esc_html_e( 'To manage your site from The Hub, log in with your WPMU DEV account email and password.', 'wpmudev' ); ?></span>

			<form method="post" action="<?php echo esc_url( $google_form_action ); ?>">
				<input type="hidden" name="context" value="connect">
				<input type="hidden" name="redirect_url" value="<?php echo esc_url( $urls->dashboard_url ); ?>">
				<input type="hidden" name="domain" value="<?php echo esc_url( WPMUDEV_Dashboard::$api->network_site_url() ); ?>">
				<input type="hidden" name="auth_nonce" value="<?php echo esc_attr( wp_create_nonce( 'auth_nonce' ) ); ?>" class="input-auth-nonce">
				<button class="sui-button dashui-google-login-button" type="submit">
					<span class="sui-icon-google-login" aria-hidden="true"></span>
					<?php esc_html_e( 'Sign in with Google', 'wpmudev' ); ?>
				</button>
			</form>

			<div class="form-separator">
				<p><?php esc_html_e( 'OR continue with email', 'wpmudev' ); ?></p>
			</div>

			<form action="<?php echo esc_url( $form_action ); ?>" method="post" class="js-wpmudev-login-form">
				<input type="hidden" name="auth_nonce" value="<?php echo esc_attr( wp_create_nonce( 'auth_nonce' ) ); ?>" class="input-auth-nonce">
				<div class="sui-form-field">
					<label for="dashboard-email" class="sui-screen-reader-text">
						<?php esc_html_e( 'Email', 'wpmudev' ); ?>
					</label>
					<input
						type="email"
						placeholder="<?php esc_html_e( 'Email', 'wpmudev' ); ?>"
						id="dashboard-email"
						name="username"
						value="<?php echo esc_attr( $last_user ); ?>"
						required="required"
						class="sui-form-control"
					/>
					<span class="sui-error-message sui-hidden js-required-message"><?php esc_html_e( 'Email is required.', 'wpmudev' ); ?></span>
					<span class="sui-error-message sui-hidden js-valid-email-message"><?php esc_html_e( 'Email is not valid.', 'wpmudev' ); ?></span>
				</div>

				<div class="sui-form-field">
					<label for="dashboard-password" class="sui-screen-reader-text">
						<?php esc_html_e( 'Password', 'wpmudev' ); ?>
					</label>
					<div class="sui-with-button sui-with-button-icon">
						<input
							type="password"
							placeholder="<?php esc_html_e( 'Password', 'wpmudev' ); ?>"
							id="dashboard-password"
							autocomplete="off"
							name="password"
							required="required"
							class="sui-form-control"
						/>
						<button class="sui-button-icon" type="button">
							<span class="sui-icon-eye" aria-hidden="true"></span>
							<span class="sui-password-text sui-screen-reader-text"><?php esc_html_e( 'Show Password', 'wpmudev' ); ?></span>
							<span class="sui-password-text sui-screen-reader-text sui-hidden"><?php esc_html_e( 'Hide Password', 'wpmudev' ); ?></span>
						</button>
						<span class="sui-error-message sui-hidden js-required-message"><?php esc_html_e( 'Password is required.', 'wpmudev' ); ?></span>
					</div>
				</div>

				<?php foreach ( $login_errors as $login_error ) : ?>
					<div class="sui-notice sui-notice-error">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
								<p><?php echo $login_error; // phpcs:ignore ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="dashui-login-button-wrap">
					<div class="dashui-sso-checkbox">
						<label for="enable-sso" class="sui-checkbox">
							<input
								type="checkbox"
								id="enable-sso"
								name="enable-sso"
								data-nonce="<?php echo esc_attr( $sso_nonce ); ?>"
								data-userid="<?php echo absint( get_current_user_id() ); ?>"
								<?php checked( true ); ?>
								value="1">
							<span aria-hidden="true"></span>
							<span class="enable-sso-label"><?php esc_html_e( 'Enable SSO', 'wpmudev' ); ?></span>
							<button
								type="button"
								class="sui-button-icon sui-tooltip sui-tooltip-top sui-tooltip-constrained"
								data-tooltip="<?php esc_html_e( 'We will automatically log you in when you visit this site from The Hub.', 'wpmudev' ); ?>">
								<span class="sui-icon-info" aria-hidden="true"></span>
							</button>
						</label>
					</div>
					<div class="dashui-login-button">

						<button class="sui-button sui-button-blue js-login-form-submit-button" type="submit">
							<span class="sui-loading-text"><?php esc_html_e( 'Connect', 'wpmudev' ); ?>&nbsp;&nbsp;<i class="sui-icon-arrow-right" aria-hidden="true"></i></span>
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						</button>

					</div>
				</div>
				<input type="hidden" name="redirect_url" value="<?php echo esc_url( $login_url ); ?>">
				<input type="hidden" name="domain" value="<?php echo esc_url( WPMUDEV_Dashboard::$api->network_site_url() ); ?>">
			</form>

		</div>

	</div>
	<div class="dashui-onboarding-footer">
		<span class="sui-description">
			<?php
			printf(
				esc_html__( "Don't have an account? %1\$sSign up%2\$s today!", 'wpmudev' ),
				'<a href="' . esc_url( $register_url ) . '" target="_blank">',
				'</a>'
			);
			?>
		</span>
		<span class="sui-description">
			<?php
			printf(
				esc_html__( '%1$sSystem Information%2$s', 'wpmudev' ),
				'<a href="' . esc_url( add_query_arg( 'view', 'system', $urls->dashboard_url ) ) . '">',
				'</a>'
			);
			?>
		</span>
	</div>
</div>