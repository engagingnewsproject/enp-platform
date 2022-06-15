<?php
declare( strict_types = 1 );

namespace WP_Defender\Component\Two_Factor\Providers;

use WP_Defender\Component\Two_Factor\Two_Factor_Provider;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_Defender\Behavior\WPMUDEV;

/**
 * Class Webauthn.
 * @since 3.0.0
 * @package WP_Defender\Component\Two_Factor\Providers
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
		add_action( 'wd_2fa_user_options_' . self::$slug, [ $this, 'user_options' ] );
	}

	/**
	 * Get the name of the provider.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return sprintf(
			'%s <span class="wpdef-notice-tag beta">%s</span>',
			$this->get_user_label(),
			__( 'Beta', 'wpdef' )
		);
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
		return __( 'Biometric', 'wpdef' );
	}

	/**
	 * Get the desc of the provider.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Use fingerprint or facial recognition from external devices to authenticate login.', 'wpdef' );
	}

	/**
	 * Display auth method.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function user_options( \WP_User $user ): void {
		$notices = [
			[
				'type'        => 'info',
				'extra_class' => 'additional-2fa-method',
				'style'       => 'display: none',
				'message'     => __( 'Access from unregistered devices will be denied. To avoid this, consider setting up an additional authentication method.', 'wpdef' ),
			],
		];

		$missed_requirement_message = $this->missed_requirement_message();
		$user_has_admin_capability = current_user_can( 'manage_options' );

		if ( $user_has_admin_capability ) {
			$notice_message = $missed_requirement_message['warning'];
		} else {
			$notice_message = __( 'Your site doesn\'t meet the requirements for biometric authentication. Please contact your site\'s administrator to fix this issue.', 'wpdef' );
		}

		$all_server_requirement_passed = $missed_requirement_message['all_server_requirement_passed'];

		if ( $all_server_requirement_passed ) {
			$message = $missed_requirement_message['browser_message'];
			$extra_class = ' browser-notice ';
		} else {
			$message = $notice_message;
			$extra_class = ' has-server-error ';
		}

		$notices[] = [
			'type'        => 'warning',
			'extra_class' => $extra_class,
			'style'       => '',
			'message'     => $message,
		];

		$this->get_controller()->render_partial(
			'two-fa/providers/biometric',
			[
				'notices' => $notices,
			]
		);
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return bool
	 */
	public function is_available_for_user( \WP_User $user ): bool {
		return true;
	}

	/**
	 * Authentication form markup.
	 *
	 * @return void
	 */
	public function authentication_form() {
		?>
		<p class="wpdef-2fa-label"><?php esc_html_e( 'Biometric authentication', 'wpdef' ); ?></p>
		<p class="wpdef-2fa-text">
			<?php esc_html_e( 'Please verify your identity using fingerprint or facial recognition.', 'wpdef' ); ?>
		</p>
		<p class="wpdef-2fa-text wpdef-2fa-biometric-process-desc">
			<img class="def-loader" src="<?php echo defender_asset_url( '/assets/img/spinner.svg' ); ?>">
			<?php esc_html_e( 'Verification in process...', 'wpdef' ); ?>
		</p>
		<input type="hidden" id="wpdef-2fa-biometric-data" name="data" />
		<input type="hidden" id="wpdef-2fa-biometric-username" name="username" />
		<input type="hidden" id="wpdef-2fa-biometric-client-id" name="client_id" />
		<input type="hidden" id="wpdef-2fa-biometric-nonce" name="_def_nonce" />
		<button class="button button-primary float-r" id="wpdef-2fa-biometric-retry-btn" type="submit" style="display:none;">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Retry', 'wpdef' ); ?>
		</button>
		<button id="wpdef-2fa-biometric-submit-btn" type="submit" style="display:none;"></button>
		<?php
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param WP_User $user
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_authentication( \WP_User $user ) {
		$webauthn_controller = wd_di()->get( \WP_Defender\Controller\Webauthn::class );
		$response = $webauthn_controller->verify_response( true );

		if ( isset( $response['success'] ) && true === $response['success'] ) {
			return true;
		} else {
			$translations = $webauthn_controller->get_translations();
			return new \WP_Error( 'opt_fail', $translations['login_failed'] );
		}
	}

	/**
	 * Create missed requirement message.
	 *
	 * Create dynamic message with boolean flag for all server requirement passed.
	 *
	 * @return array Dynamic message with boolean flag for all server requirement passed.
	 */
	private function missed_requirement_message(): array {
		$all_server_requirement_passed = true;
		$warning_core_message = '';

		if ( ! $this->is_ssl() ) {
			$all_server_requirement_passed = false;
			$warning_core_message .= __( 'SSL certificate or HTTPS is not forced. ', 'wpdef' );
		}

		$extension_warning_message = $this->get_extension_warning_message();

		if ( $extension_warning_message !== '' ) {
			$all_server_requirement_passed = false;
			$warning_core_message .= $extension_warning_message;
		}

		$browser_extension_message = sprintf(
			__(
				'<span class="browser-incompatible-msg">The current browser version <span class="browser-version">%s</span> is not compatible with this feature, please update your browser version, or try a different one. </span>',
				'wpdef'
			),
			$this->get_browser_name()
		);

		$warning_prefix = __( 'Your site doesn\'t meet the requirements for biometric authentication. ', 'wpdef' );
		$warning_suffix = $this->get_warning_suffix();

		$warning = $warning_prefix . $warning_core_message . $browser_extension_message . $warning_suffix;

		$browser_message = $warning_prefix . $browser_extension_message . $warning_suffix;

		return [
			'warning' => $warning,
			'all_server_requirement_passed' => $all_server_requirement_passed,
			'browser_message' => $browser_message,
		];
	}

	/**
	 * Get customer support URL.
	 *
	 * @return string return customer support URL.
	 */
	private function customer_support_url(): string {
		$pro_url = 'https://wpmudev.com/hub2/support/#get-support';

		return $pro_url;
	}

	/**
	 * Get list of failed extensions.
	 *
	 * @return array Array of failed extensions or empty array if all extensions exists.
	 */
	private function get_failed_extension(): array {
		$failed_extensions = [];

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
	 *
	 * Form a comma separated with and before last element string from array of elements, if elements is more than two.
	 *
	 * @param array $extensions list of failed extensions.
	 *
	 * @return string Return concatenated string on success else empty string.
	 */
	private function concatenate_failed_extensions( array $extensions ): string {
		if ( count( $extensions ) === 1 ) {
			return $extensions[0];
		}

		if ( count( $extensions ) > 1 ) {
			$last_extension = __( ' and ', 'wpdef' ) . array_pop( $extensions );

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
		$message = '';

		$failed_extensions = $this->get_failed_extension();

		if ( count( $failed_extensions ) > 0 ) {
			$extension = __( 'extension', 'wpdef' );
			$verb = __( 'is', 'wpdef' );

			if ( count( $failed_extensions ) > 1 ) {
				$extension = __( 'extensions', 'wpdef' );
				$verb = __( 'are', 'wpdef' );
			}

			$extension_string = $this->concatenate_failed_extensions( $failed_extensions );

			$message .= sprintf(
				__(
					'The <span class="extensions">%1$s %2$s</span> %3$s not active on your server. ',
					'wpdef'
				),
				$extension_string,
				$extension,
				$verb
			);
		}

		return $message;
	}

	/**
	 * Get browser name with optional version concatenated.
	 *
	 * @return string Browser name with or without version.
	 */
	private function get_browser_name( $with_version = true ): string {
		if ( ! function_exists( 'wp_check_browser_version' ) ) {
			include_once ABSPATH . 'wp-admin/includes/dashboard.php';
		}

		$browser = wp_check_browser_version();
		$browser_string = $browser['name'] . ( $with_version ? ' ' . $browser['version'] : '' );

		return $browser_string;
	}

	/**
	 * Determine the warning suffix.
	 *
	 * @return string Warning suffix sentence.
	 */
	private function get_warning_suffix(): string {
		$support_link = $this->customer_support_url();

		$warning_suffix = '';

		if ( ( new WPMUDEV() )->show_support_links() ) {
			$warning_suffix = sprintf(
				__(
					'Still, having trouble? <a target="_blank" href="%s">Open a support ticket</a>.',
					'wpdef'
				),
				$support_link
			);
		}

		return $warning_suffix;
	}

}
