<?php
/**
 * After login screen.
 *
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls Urls class.
 *
 * @package wpmudev
 */

// The Hub – Images.
$hub          = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub.png';
$hub_tilted   = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub-tilted.png';
$hub2x        = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub@2x.png';
$hub_tilted2x = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/hub-tilted@2x.png';
// WordPress - Images.
$wordpress          = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress.png';
$wordpress_tilted   = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress-tilted.png';
$wordpress2x        = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress@2x.png';
$wordpress_tilted2x = WPMUDEV_Dashboard::$site->plugin_url . '/assets/images/onboarding/wordpress-tilted@2x.png';
// Logo.
$logo   = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo.png';
$logo2x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo@2x.png';
$logo3x = WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/onboarding/login/logo@3x.png';

$auth_verify_nonce = wp_verify_nonce( ( isset( $_REQUEST['auth_nonce'] ) ? $_REQUEST['auth_nonce'] : '' ), 'auth_nonce' );
$key = isset( $_GET['key'] ) ? trim( $_GET['key'] ) : '';
if ( ! $auth_verify_nonce || empty( $key ) ) {
	WPMUDEV_Dashboard::$ui->redirect_to( $urls->dashboard_url );
}
?>

<div class="dashui-onboarding">
	<div
		class="dashui-onboarding-body dashui-onboarding-content-center js-login-sync"
		data-key="<?php echo esc_attr( $key ); ?>"
		data-dashurl="<?php echo esc_url( $urls->dashboard_url ); ?>"
		data-hash="<?php echo esc_attr( wp_create_nonce( 'hub-sync' ) ); ?>"
	>
		<div class="dashui-login-form animate-sync animate-1">
			<img
				src="<?php echo esc_url( $logo ); ?>"
				srcset="<?php echo esc_url( $logo ); ?> 1x, <?php echo esc_url( $logo2x ); ?> 2x, <?php echo esc_url( $logo3x ); ?> 3x"
				class="dashui-onboarding-logo"
				alt="<?php esc_html_e( 'Connecting...', 'wpmudev' ); ?>"
			/>

			<h2><?php esc_html_e( 'Connecting...', 'wpmudev' ); ?></h2>
			<span class="sui-description"><?php esc_html_e( 'Please wait a few moments while we connect your website.', 'wpmudev' ); ?></span>
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
					<div class="dashui-connect-loading-bar"></div>
					<div class="dashui-connect-image">
						<img
							src="<?php echo esc_url( $wordpress ); ?>"
							srcset="<?php echo esc_url( $wordpress ); ?> 1x, <?php echo esc_url( $wordpress2x ); ?> 2x"
							class="sui-image"
							alt=""
						/>
					</div>
				</div>
				<div class="dashui-connect-body">
					<p class="dashui-stage-text"><?php esc_html_e( 'The Hub connects WPMU DEV to your website and unlocks all the power of our all-in-one platform services.', 'wpmudev' ); ?></p>
				</div>
				<div class="dashui-connect-navigation" aria-hidden="true">
					<span class="dashui-current"></span>
					<span></span>
				</div>
			</div>
		</div>

		<div class="dashui-login-form animate-sync sui-hidden animate-2">
			<h2><?php esc_html_e( 'Connecting...', 'wpmudev' ); ?></h2>
			<span class="sui-description"><?php esc_html_e( 'Please wait a few moments while we connect your website.', 'wpmudev' ); ?></span>
			<div class="dashui-connect">
				<div class="dashui-connect-header" aria-hidden="true">
					<div class="dashui-connect-image">
						<img
							src="<?php echo esc_url( $hub_tilted ); ?>"
							srcset="<?php echo esc_url( $hub_tilted ); ?> 1x, <?php echo esc_url( $hub_tilted2x ); ?> 2x"
							class="sui-image"
							alt=""
						/>
					</div>
					<div class="dashui-connect-loading-bar"></div>
					<div class="dashui-connect-image">
						<img
							src="<?php echo esc_url( $wordpress_tilted ); ?>"
							srcset="<?php echo esc_url( $wordpress_tilted ); ?> 1x, <?php echo esc_url( $wordpress_tilted2x ); ?> 2x"
							class="sui-image"
							alt=""
						/>
					</div>
				</div>

				<div class="dashui-connect-body dashui-final-stage">
					<p class="dashui-stage-text">
						<?php esc_html_e( 'Once your website is connected to the Hub, you will be able to perform updates, managing services - all from one place.', 'wpmudev' ); ?>
					</p>
				</div>

				<div class="dashui-connect-navigation" aria-hidden="true">
					<span></span>
					<span class="dashui-current"></span>
				</div>
			</div>
		</div>
	</div>
</div>