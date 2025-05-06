<?php
/**
 * Fallback Email Two-Factor Authentication Provider.
 *
 * @package WP_Defender\Component\Two_Factor\Providers
 */

namespace WP_Defender\Component\Two_Factor\Providers;

use WP_User;
use WP_Error;
use Calotes\Helper\HTTP;
use WP_Defender\Component\Crypt;
use WP_Defender\Component\Two_Factor\Two_Factor_Provider;

/**
 * Handles fallback email for two-factor authentication.
 *
 * @since 2.8.0
 */
class Fallback_Email extends Two_Factor_Provider {

	/**
	 * 2fa provider slug.
	 *
	 * @var string
	 */
	public static $slug = 'fallback-email';

	/**
	 * User meta key to store the fallback email address.
	 *
	 * @var string
	 */
	public const FALLBACK_EMAIL_KEY = 'defenderAuthEmail';

	/**
	 * User meta key to store the backup code.
	 *
	 * @var string
	 */
	public const FALLBACK_BACKUP_CODE_KEY = 'defenderBackupCode';

	/**
	 * Constructor to add hooks related to the fallback email options.
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
		return esc_html__( 'Fallback Email', 'wpdef' );
	}

	/**
	 * Returns the login label for the fallback email provider.
	 *
	 * @return string
	 */
	public function get_login_label(): string {
		return $this->get_label();
	}

	/**
	 * Returns the user label for the fallback email provider.
	 *
	 * @return string
	 */
	public function get_user_label(): string {
		return esc_html__( 'Fallback email', 'wpdef' );
	}

	/**
	 * Get the desc of the provider.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return esc_html__(
			'If you ever lose your device, you can send a fallback passcode to this email address.',
			'wpdef'
		);
	}

	/**
	 * Display auth method.
	 *
	 * @param  WP_User $user  The user object.
	 */
	public function user_options( WP_User $user ) {
		$service = $this->get_component();
		$class   = $service->is_checked_enabled_provider_by_slug( $user, self::$slug ) ? '' : 'hidden';
		$this->get_controller()->render_partial(
			'two-fa/providers/fallback-email',
			array(
				'class'        => $class,
				'backup_email' => self::get_backup_email( $user->ID ),
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
	 * Outputs the form needed for authentication with this provider.
	 */
	public function authentication_form() {
		?>
		<p class="wpdef-2fa-label"><?php echo $this->get_login_label(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<p class="wpdef-2fa-text">
			<?php
			echo esc_html__(
				"We've sent a one-time passcode to your fallback email address. Enter it below to log in to your account.",
				'wpdef'
			);
			?>
		</p>
		<input type="text" autofocus value="" autocomplete="off" name="otp"/>
		<button class="button button-primary float-r" type="submit">
			<?php
			esc_attr_e(
				'Authenticate',
				'wpdef'
			);
			?>
		</button>
		<p class="wpdef-2fa-email-resend">
			<input type="submit" class="button" name="button_resend_code"
					value="<?php esc_attr_e( 'Resend Code', 'wpdef' ); ?>"/>
		</p>
		<?php
	}

	/**
	 * Retrieves the backup email address for the specified user.
	 *
	 * @param  int|null $user_id  The user ID. If null, the current user ID is used.
	 *
	 * @return bool|string The email address or false if not found.
	 */
	public static function get_backup_email( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$email = get_user_meta( $user_id, self::FALLBACK_EMAIL_KEY, true );
		if ( empty( $email ) ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! is_object( $user ) ) {
				return false;
			}
			$email = $user->user_email;
		}

		return $email;
	}

	/**
	 * Whether this 2FA provider is configured and available for the user specified.
	 *
	 * @param  WP_User $user  The user object.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_authentication( $user ) {
		$otp         = HTTP::post( 'otp', '' );
		$backup_code = get_user_meta( $user->ID, self::FALLBACK_BACKUP_CODE_KEY, true );
		if ( ! empty( $backup_code ) && Crypt::compare_lines( $backup_code['code'], wp_hash( $otp ) )
			&& strtotime( '+3 minutes', $backup_code['time'] ) > time()
		) {
			delete_user_meta( $user->ID, self::FALLBACK_BACKUP_CODE_KEY );

			return true;
		} else {
			$lockout_message = $this->get_component()->verify_attempt( $user->ID, self::$slug );

			return new WP_Error(
				'opt_fail',
				empty( $lockout_message ) ? esc_html__( 'ERROR: Invalid passcode.', 'wpdef' ) : $lockout_message
			);
		}
	}
}