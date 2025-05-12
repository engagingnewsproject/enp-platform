<?php
/**
 * Handles Web Authentication (WebAuthn) for Two-Factor Authentication.
 *
 * @package WP_Defender\Component\Two_Factor\Providers
 */

namespace WP_Defender\Component\Two_Factor\Providers;

use WP_User;
use WP_Error;
use WP_Defender\Component\Two_Fa;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_Defender\Component\Webauthn as Webauthn_Component;
use WP_Defender\Component\Two_Factor\Two_Factor_Provider;
use WP_Defender\Controller\Webauthn as Webauthn_Controller;

/**
 * Handle Web Authentication (WebAuthn) for Two-Factor Authentication.
 *
 * @since 3.0.0
 */
class Webauthn extends Two_Factor_Provider {

	use Webauthn_Trait;

	/**
	 * 2fa provider slug.
	 *
	 * @var string
	 */
	public static $slug = 'webauthn';

	/**
	 * Constructor for the Biometric provider.
	 */
	public function __construct() {
		add_action( 'wd_2fa_user_options_' . self::$slug, array( $this, 'user_options' ) );
	}

	/**
	 * Get the name of the provider.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->get_user_label();
	}

	/**
	 * Get login label.
	 *
	 * @return string
	 */
	public function get_login_label(): string {
		return $this->get_label();
	}

	/**
	 * Get user label.
	 *
	 * @return string
	 */
	public function get_user_label(): string {
		return esc_html__( 'Web Authentication', 'wpdef' );
	}

	/**
	 * Get the desc of the provider.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return esc_html__(
			"Authenticate login using your device's built-in fingerprint, facial recognition, or an external hardware security key.",
			'wpdef'
		);
	}

	/**
	 * Display auth method.
	 *
	 * @param  WP_User $user  WP_User object of the logged-in user.
	 */
	public function user_options( WP_User $user ): void {
		$notices = array(
			array(
				'type'        => 'info',
				'extra_class' => 'additional-2fa-method',
				'style'       => 'display: none',
				'message'     => esc_html__(
					'Access from unregistered devices will be denied. To avoid this, consider setting up an additional authentication method.',
					'wpdef'
				),
			),
		);

		$missed_requirement_message = $this->missed_requirement_message();
		$user_has_admin_capability  = current_user_can( 'manage_options' );

		if ( $user_has_admin_capability ) {
			$notice_message = $missed_requirement_message['warning'];
		} else {
			$notice_message = esc_html__(
				'Your site doesn\'t meet the web authentication requirement. Please contact your site\'s administrator to fix this issue.',
				'wpdef'
			);
		}

		$all_server_requirement_passed = $missed_requirement_message['all_server_requirement_passed'];

		if ( $all_server_requirement_passed ) {
			$message     = $missed_requirement_message['browser_message'];
			$extra_class = ' browser-notice ';
		} else {
			$message     = $notice_message;
			$extra_class = ' has-server-error ';
		}

		$notices[] = array(
			'type'        => 'warning',
			'extra_class' => $extra_class,
			'style'       => '',
			'message'     => $message,
		);

		$translations = wd_di()->get( Webauthn_Controller::class )->get_translations();
		$notices[]    = array(
			'type'           => 'warning',
			'extra_class'    => ' user-handle-mismatch ',
			'style'          => 'display: none',
			'is_dismissible' => true,
			'message'        => $translations['user_handle_mismatch_main_notice'],

		);

		$this->get_controller()->render_partial(
			'two-fa/providers/biometric',
			array(
				'notices' => $notices,
			)
		);
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param  WP_User $user  WP_User object of the logged-in user.
	 *
	 * @return bool
	 */
	public function is_available_for_user( WP_User $user ): bool {
		return true;
	}

	/**
	 * Authentication form markup.
	 *
	 * @return void
	 */
	public function authentication_form() {
		?>
		<div class="welcome-screen">
			<p class="wpdef-2fa-label"><?php echo $this->get_login_label(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<p><?php esc_html_e( 'Select an authentication method to continue.', 'wpdef' ); ?></p>
			<div class="option-row" data-authType="platform">
				<span class="icon biometric"></span>
				<span>
					<strong class="option-hd"><?php esc_html_e( 'Biometric Authentication', 'wpdef' ); ?></strong>
					<?php esc_html_e( 'Use fingerprint or facial recognition', 'wpdef' ); ?>
				</span>
			</div>
			<div class="option-row" data-authType="cross-platform">
				<span class="icon hardware"></span>
				<span>
					<strong class="option-hd"><?php esc_html_e( 'Hardware Key Authentication', 'wpdef' ); ?></strong>
					<?php esc_html_e( 'Use USB security keys to login', 'wpdef' ); ?>
				</span>
			</div>
		</div>
		<div class="webauthn-content-wrap">
			<div class="webauthn-platform" style="display:none;">
				<p class="wpdef-2fa-label"><?php esc_html_e( 'Biometric Authentication', 'wpdef' ); ?></p>
				<p class="wpdef-2fa-text">
					<?php
					esc_html_e(
						'Please verify your identity using fingerprint or facial recognition.',
						'wpdef'
					);
					?>
				</p>
			</div>
			<div class="webauthn-cross-platform" style="display:none;">
				<p class="wpdef-2fa-label"><?php esc_html_e( 'Hardware Key Authentication', 'wpdef' ); ?></p>
				<p class="wpdef-2fa-text">
					<?php esc_html_e( 'Insert and tap on your USB security key to login.', 'wpdef' ); ?>
				</p>
			</div>
			<p class="wpdef-2fa-text wpdef-2fa-biometric-process-desc" style="display: none;">
				<img class="def-loader" src="<?php defender_asset_url( '/assets/img/spinner.svg', true ); ?>"
					alt="loading">
				<?php esc_html_e( 'Verification in process...', 'wpdef' ); ?>
			</p>
			<div class="wpdef-2fa-webauthn-control" style="display:none;">
				<button type="button" class="button"
						id="wpdef-2fa-webauthn-back-btn"><?php esc_html_e( 'Back', 'wpdef' ); ?></button>
				<button type="button" class="button button-primary float-r" id="wpdef-2fa-biometric-retry-btn">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Retry', 'wpdef' ); ?>
				</button>
			</div>
			<input type="hidden" id="wpdef-2fa-biometric-data" name="data"/>
			<input type="hidden" id="wpdef-2fa-biometric-username" name="username"/>
			<input type="hidden" id="wpdef-2fa-biometric-client-id" name="client_id"/>
			<input type="hidden" id="wpdef-2fa-biometric-nonce" name="_def_nonce"/>
			<button id="wpdef-2fa-biometric-submit-btn" type="submit" style="display:none;"></button>
		</div>
		<?php
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param  WP_User $user  The user to authenticate.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_authentication( WP_User $user ) {
		$webauthn_controller = wd_di()->get( Webauthn_Controller::class );
		$response            = $webauthn_controller->verify_response( true );

		if ( isset( $response['success'] ) && true === $response['success'] ) {
			return true;
		} else {
			// No attempt-checks.
			$translations = $webauthn_controller->get_translations();

			if ( 'Invalid user handle' === $response['data']['message'] ) {
				$message = $translations['login_user_handle_match_failed'];
			} else {
				$message = $translations['login_failed'];
			}

			return new WP_Error( 'opt_fail', $message );
		}
	}

	/**
	 * Create missed requirement message.
	 * Create dynamic message with boolean flag for all server requirement passed.
	 *
	 * @return array Dynamic message with boolean flag for all server requirement passed.
	 */
	private function missed_requirement_message(): array {
		$warning_count                 = 0;
		$all_server_requirement_passed = true;
		$warning_core_message          = '';

		if ( ! $this->is_ssl() ) {
			++$warning_count;
			$all_server_requirement_passed = false;
			$warning_core_message         .= esc_html__( 'SSL certificate or HTTPS is not forced. ', 'wpdef' );
		}

		$extension_warning_message = $this->get_extension_warning_message();

		if ( '' !== $extension_warning_message ) {
			$warning_count                += count( $this->get_failed_extension() );
			$all_server_requirement_passed = false;
			$warning_core_message         .= $extension_warning_message;
		}

		$browser_extension_message  = '<span class="browser-incompatible-msg">';
		$browser_extension_message .= sprintf(
		/* translators: %s: Browser version */
			esc_html__(
				'The current browser version %s is not compatible with this feature, please update your browser version, or try a different one. ',
				'wpdef'
			),
			'<span class="browser-version">' . $this->get_browser_name() . '</span>'
		);
		$browser_extension_message .= '</span>';

		$warning_prefix = _n(
			'Your site doesn\'t meet the web authentication requirement. ',
			'Your site doesn\'t meet the web authentication requirements. ',
			$warning_count,
			'wpdef'
		);
		$warning_suffix = $this->get_warning_suffix();

		$warning = $warning_prefix . $warning_core_message . $browser_extension_message . $warning_suffix;

		$browser_message = $warning_prefix . $browser_extension_message . $warning_suffix;

		return array(
			'warning'                       => $warning,
			'all_server_requirement_passed' => $all_server_requirement_passed,
			'browser_message'               => $browser_message,
		);
	}

	/**
	 * Get list of failed extensions.
	 *
	 * @return array Array of failed extensions or empty array if all extensions exists.
	 */
	private function get_failed_extension(): array {
		$failed_extensions = array();

		if ( ! $this->is_enabled_gmp() ) {
			$failed_extensions[] = 'GMP';
		}

		if ( ! $this->is_enabled_mbstring() ) {
			$failed_extensions[] = 'MBSTRING';
		}

		if ( ! $this->is_enabled_sodium() ) {
			$failed_extensions[] = 'SODIUM';
		}

		return $failed_extensions;
	}

	/**
	 * Concatenate multiple failed extensions.
	 * Form a comma separated with and before last element string from array of elements, if elements is more than two.
	 *
	 * @param  array $extensions  list of failed extensions.
	 *
	 * @return string Return concatenated string on success else empty string.
	 */
	private function concatenate_failed_extensions( array $extensions ): string {
		if ( count( $extensions ) === 1 ) {
			return $extensions[0];
		}

		if ( count( $extensions ) > 1 ) {
			$last_extension = esc_html__( ' and ', 'wpdef' ) . array_pop( $extensions );

			return implode( ', ', $extensions ) . $last_extension;
		}

		return '';
	}

	/**
	 * Create extension warning message
	 *
	 * @return string Warning message if extension not met the requirement or empty string for met requirement.
	 */
	private function get_extension_warning_message(): string {
		$message                 = '';
		$failed_extensions       = $this->get_failed_extension();
		$failed_extensions_count = count( $failed_extensions );

		if ( $failed_extensions_count > 0 ) {
			$extension_string = $this->concatenate_failed_extensions( $failed_extensions );

			$message .= sprintf(
			/* translators: %s: Missing PHP extension(s) */
				_n(
					'The <strong>%s extension</strong> is not active on your server. ',
					'The <strong>%s extensions</strong> are not active on your server. ',
					$failed_extensions_count,
					'wpdef'
				),
				$extension_string
			);
		}

		return $message;
	}

	/**
	 * Retrieves the browser name and optionally the version.
	 *
	 * @param  bool $with_version  Whether to include the version number.
	 *
	 * @return string Browser name, optionally including the version.
	 */
	private function get_browser_name( $with_version = true ): string {
		if ( ! function_exists( 'wp_check_browser_version' ) ) {
			include_once ABSPATH . 'wp-admin/includes/dashboard.php';
		}

		$browser        = wp_check_browser_version();
		$browser_string = $browser['name'] . ( $with_version ? ' ' . $browser['version'] : '' );

		return $browser_string;
	}

	/**
	 * Determine the warning suffix.
	 *
	 * @return string Warning suffix sentence.
	 */
	private function get_warning_suffix(): string {
		$warning_suffix = '';
		if ( ( new WPMUDEV() )->show_support_links() ) {
			$warning_suffix = defender_support_ticket_text();
		}

		return $warning_suffix;
	}

	/**
	 * Check if OTP screen should be shown to user.
	 *
	 * @param  WP_User $user  The user to check.
	 *
	 * @return bool
	 * @since 3.3.0
	 */
	public function is_otp_screen_available( WP_User $user ): bool {
		$is_available      = false;
		$service_two_fa    = wd_di()->get( Two_Fa::class );
		$enabled_providers = $service_two_fa->get_enabled_providers_for_user( $user );

		if ( ! empty( $user->ID ) && true === in_array( self::$slug, $enabled_providers, true ) ) {
			$controller       = wd_di()->get( Webauthn_Controller::class );
			$service_webauthn = wd_di()->get( Webauthn_Component::class );
			$user_entity      = $controller->get_user_entity( $user->ID );

			if ( false !== $user_entity ) {
				$credential_sources = $service_webauthn->findAllForUserEntity( $user_entity );

				if ( is_array( $credential_sources ) && 0 < count( $credential_sources ) ) {
					$is_available = true;
				}
			}
		}

		return $is_available;
	}
}