<?php
/**
 * Enforces strong password policies for user accounts.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_User;
use WP_Error;
use stdClass;
use WP_Defender\Component;
use WP_Defender\Traits\User;
use WP_Defender\Model\Setting\Strong_Password as Settings;

/**
 *  Enforces strong password policies for user accounts.
 */
class Strong_Password extends Component {
	use User;

	/**
	 * Cookie key to show warning message on reset password page.
	 *
	 * @var string
	 */
	protected const COOKIE_KEY = 'display_strong_password_warning';

	/**
	 * Error code.
	 *
	 * @var string
	 */
	protected const CODE = 'password_strength';

	/**
	 * Password reset settings model.
	 *
	 * @var Settings|null
	 */
	protected ?Settings $model;

	/**
	 * Helper instance for reuse methods from pwned password protection.
	 *
	 * @var Password_Protection|null
	 */
	protected ?Password_Protection $helper;

	/**
	 * Constructs the object, setting the model to a Settings instance.
	 */
	public function __construct() {
		$this->model  = wd_di()->get( Settings::class );
		$this->helper = wd_di()->get( Password_Protection::class );
	}

	/**
	 * Filters whether the given user can be authenticated with the provided password.
	 *
	 * @param WP_User|WP_Error $user WP_User or WP_Error object if a previous callback failed authentication.
	 * @param string           $password Password to check against the user.
	 *
	 * @return WP_User|WP_Error WP_User or WP_Error object if a previous callback failed authentication.
	 */
	public function during_authentication( $user, $password ) {
		// Check if the user object is valid.
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}

		// Check if the password is empty.
		if ( empty( $password ) ) {
			return new WP_Error(
				'defender_invalid_password',
				esc_html__( 'Invalid user data.', 'wpdef' )
			);
		}

		// Verify the provided password against the user's password.
		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return $user;
		}

		// Check if the user's role requires strong password enforcement.
		if ( ! $this->should_enforce_for_user( $user ) ) {
			return $user;
		}

		// Validate the strength of the provided password.
		if ( $this->is_weak_password( $password ) ) {
			$this->helper->trigger_redirect( $user, self::CODE, self::COOKIE_KEY );
			exit;
		}

		return $user;
	}

	/**
	 * Check if a password is weak.
	 *
	 * Password requirements:
	 * - At least 12 characters long.
	 * - Must contain both uppercase and lowercase letters.
	 * - At least one symbol.
	 * - At least one number.
	 *
	 * @param string $password The password to validate.
	 *
	 * @return bool True if the password is weak, false otherwise.
	 */
	private function is_weak_password( $password ): bool {
		// Check minimum length.
		if ( strlen( $password ) < 12 ) {
			return true;
		}

		// Check for at least one uppercase letter.
		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			return true;
		}

		// Check for at least one lowercase letter.
		if ( ! preg_match( '/[a-z]/', $password ) ) {
			return true;
		}

		// Check for at least one number.
		if ( ! preg_match( '/[0-9]/', $password ) ) {
			return true;
		}

		// Check for at least one special character or symbol.
		$symbol_pattern = '/[!@#$%^&*()_+\-={};:\'",.<>?~\[\]\/|`]/';
		if ( ! preg_match( $symbol_pattern, $password ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue custom password strength script on relevant admin pages.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function scripts( $hook_suffix ) {
		// Check if the current admin page is relevant.
		if ( '' === $hook_suffix ) {
			global $pagenow;
			$hook_suffix = $pagenow;
		}

		if ( 'user-edit.php' === $hook_suffix ) {
			// Changes for another existing user? Skip case for a new user on /wp-admin/user-new.php.
			global $profile_user;
			$user_obj = $profile_user;
		} elseif ( 'profile.php' === $hook_suffix ) {
			// Changes for the current user.
			global $current_user;
			$user_obj = $current_user;
		} else {
			$user_obj = null;
		}
		// Prevent strong password enforcement for exempt users.
		if ( isset( $user_obj ) && ! $this->should_enforce_for_user( $user_obj ) ) {
			return;
		}
		global $user;
		if ( isset( $user ) && ! $this->should_enforce_for_user( $user ) ) {
			return;
		}
		// Collect all locations.
		$pages = array( 'profile.php', 'user-new.php', 'user-edit.php', 'wp-login.php' );
		if ( in_array( $hook_suffix, $pages, true ) ) {
			wp_enqueue_style( 'wd-strong-password', plugins_url( 'assets/css/strong-password.css', WP_DEFENDER_FILE ), array( 'dashicons' ), DEFENDER_VERSION );
			wp_enqueue_script(
				'wd-strong-password',
				plugins_url( 'assets/js/strong-password.js', WP_DEFENDER_FILE ),
				array(
					'jquery',
					'password-strength-meter',
				),
				DEFENDER_VERSION,
				true
			);
			wp_localize_script(
				'wd-strong-password',
				'wpdef_pws_strings',
				array(
					'message'      => esc_html__( 'Hint: Your password must follow the guidelines below.', 'wpdef' ),
					'requirements' => array(
						'length' => esc_html__( 'At least 12 characters', 'wpdef' ),
						'case'   => esc_html__( 'Uppercase and lowercase letters', 'wpdef' ),
						'symbol' => esc_html__( 'At least one symbol', 'wpdef' ),
						'number' => esc_html__( 'At least one number', 'wpdef' ),
						'zxcvbn' => esc_html__( 'Avoid common words or sequences of letters/numbers', 'wpdef' ),
					),
				)
			);
			add_filter( 'password_hint', '__return_empty_string' );
		}
	}

	/**
	 * Validate password during user profile update.
	 *
	 * @param WP_Error         $errors WP_Error object.
	 * @param bool             $update Whether this is a user update.
	 * @param stdClass|WP_User $user User object.
	 */
	public function on_profile_update( $errors, $update, $user ) {
		if (
			// If there are already errors with the password, exit.
			$errors->get_error_message( 'pass' ) ||
			// If the user object is invalid, exit.
			is_wp_error( $user ) ||
			// If the user's password is not set, exit.
			! isset( $user->user_pass )
		) {
			return;
		}

		// When updating the profile check if user's role preference is enabled.
		if ( ! $this->should_enforce_for_user( $user ) ) {
			return;
		}

		$login_password = $this->helper->get_submitted_password();

		// Check if the submitted password is weak.
		if ( isset( $login_password ) && $this->is_weak_password( $login_password ) ) {
			$errors->add( self::CODE, $this->model->get_message() );
		}
	}

	/**
	 * Validate password during password reset.
	 *
	 * @param WP_Error $errors WP_Error object.
	 * @param WP_User  $user WP_User object.
	 */
	public function on_password_reset( $errors, $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}

		if ( ! $this->should_enforce_for_user( $user ) ) {
			return;
		}

		// Check if display_strong_password_warning cookie enabled then show warning message on reset password page.
		$cookie_value = isset( $_COOKIE[ self::COOKIE_KEY ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_KEY ] ) ) : '';

		// If the cookie is set, show the warning message.
		if ( ! empty( $cookie_value ) ) {
			$errors->add( self::CODE, $this->model->get_message() );

			// Remove the cookie.
			$this->helper->remove_cookie_notice( self::COOKIE_KEY );
		}

		$submitted_password = $this->helper->get_submitted_password();

		// Check if the submitted password is weak.
		if ( ! empty( $submitted_password ) && $this->is_weak_password( $submitted_password ) ) {
			$errors->add( self::CODE, $this->model->get_message() );
		}
	}
}