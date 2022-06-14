<?php

namespace WP_Defender\Component\Two_Factor\Providers;

use Calotes\Helper\HTTP;
use WP_Defender\Component\Two_Factor\Two_Factor_Provider;

/**
 * Class Totp
 * Note: key 'defenderAuthOn' only for TOTP method.
 *
 * @since 2.8.0
 * @package WP_Defender\Component\Two_Factor\Providers
 */
class Totp extends Two_Factor_Provider {
	static $slug = 'totp';

	protected $label;

	protected $description;

	public function __construct() {
		add_action( 'wd_2fa_init_provider_' . self::$slug, array( &$this, 'init_provider' ) );
		add_action( 'wd_2fa_user_options_' . self::$slug, array( &$this, 'user_options' ) );
	}

	/**
	 * Get the name of the provider.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'TOTP Authenticator App', 'wpdef' ) . $this->label;
	}

	/**
	 * @return string
	 */
	public function get_login_label() {
		return __( 'TOTP Authentication', 'wpdef' );
	}

	/**
	 * @return string
	 */
	public function get_user_label() {
		return __( 'TOTP', 'wpdef' );
	}

	/**
	 * Get the desc of the provider.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	public function authentication_form() {
		?>
		<p class="wpdef-2fa-label"><?php echo $this->get_login_label(); ?></p>
		<p class="wpdef-2fa-text def-otp-text"><?php esc_html_e( $this->get_model()->app_text ); ?></p>
		<input type="text" autofocus value="" autocomplete="off" name="otp" />
		<button class="button button-primary float-r" type="submit"><?php _e( "Authenticate", 'wpdef' ) ?></button>
		<?php
	}

	/**
	 * @return array
	 */
	public function get_auth_apps() {
		return array(
			'google-authenticator'    => 'Google Authenticator',
			'microsoft-authenticator' => 'Microsoft Authenticator',
			'authy'                   => 'Authy',
		);
	}

	/**
	 * @param \WP_User $user
	 */
	public function init_provider( $user ) {
		$is_on             = $this->is_available_for_user( $user );
		$this->label       = $is_on
			? sprintf(
			/* translators: %s: style class */
				__( '<button type="button" class="button reset-totp-keys button-secondary hide-if-no-js" %s>Reset Keys</button>', 'wpdef' ),
				$this->get_component()->is_checked_enabled_provider_by_slug( $user, self::$slug ) ? '' : ' disabled'
			)
			: '';
		$this->description = $is_on
			? __( 'TOTP Authentication method is active for this site', 'wpdef' )
			: __( 'Use an authenticator app to sign in with a separate passcode.', 'wpdef' );
	}

	/**
	 * Display auth method.
	 *
	 * @param WP_User $user
	 */
	public function user_options( $user ) {
		if ( ! wp_script_is( 'clipboard', 'enqueued' ) ) {
			wp_enqueue_script( 'clipboard' );
		}
		$model          = $this->get_model();
		$service        = $this->get_component();
		$default_values = $model->get_default_values();
		$is_on          = $this->is_available_for_user($user);
		if ( $is_on ) {
			$this->get_controller()->render_partial(
				'two-fa/providers/totp-enabled',
				array(
					'url' => $this->get_url( 'disable_totp' ),
				)
			);
		} else {
			$this->get_controller()->render_partial(
				'two-fa/providers/totp-disabled',
				array(
					'url'             => $this->get_url( 'verify_otp_for_enabling' ),
					'default_message' => $default_values['message'],
					'auth_apps'       => $this->get_auth_apps(),
					'user'            => $user,
					'secret_key'      => $service::get_user_secret(),
					'class'           => $service->is_checked_enabled_provider_by_slug( $user, self::$slug ) ? '' : 'hidden',
				)
			);
		}
	}

	/**
	 * Generate a QR code for apps can use. Apps from get_auth_apps().
	 *
	 * @param string $secret_key
	 */
	public static function generate_qr_code( $secret_key ) {
		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		$issuer   = $settings->app_title;
		$user     = wp_get_current_user();
		$chl      = ( 'otpauth://totp/' . rawurlencode( $issuer ) . ':' . rawurlencode( $user->user_email ) . '?secret=' . $secret_key . '&issuer=' . rawurlencode( $issuer ) );
		require_once defender_path( 'src/extra/phpqrcode/phpqrcode.php' );

		\QRcode::svg( $chl, false, QR_ECLEVEL_L, 4 );
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return boolean
	 */
	public function is_available_for_user( $user ) {
		return (bool) get_user_meta( $user->ID, 'defenderAuthOn', true );
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_authentication( $user ) {
		$otp = HTTP::post( 'otp' );
		if ( empty( $otp ) ) {
			return new \WP_Error(
				'opt_fail',
				__( 'Whoops, the passcode you entered was incorrect or expired.', 'wpdef' )
			);
		}

		return $this->get_component()->verify_otp( $otp, $user );
	}
}
