<?php
/**
 * Responsible for managing the login duration settings in a WordPress environment.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

use WP_Error;
use Calotes\Helper\HTTP;
use WP_Defender\Traits\Security_Tweaks_Option;

/**
 * Manages the login duration settings.
 */
class Login_Duration extends Abstract_Security_Tweaks implements Security_Key_Const_Interface {

	use Security_Tweaks_Option;

	public const DEFAULT_DAYS = 14;
	public const OPTION_NAME  = 'defender_security_tweeks_login-duration';
	/**
	 * Slug identifier for the component.
	 *
	 * @var string
	 */
	public string $slug = 'login-duration';
	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @var bool
	 */
	public $resolved = false;

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return $this->resolved;
	}

	/**
	 * Validates if the provided duration is a positive number.
	 *
	 * @param  string|int $duration  The duration to validate.
	 *
	 * @return bool True if the duration is incorrect, false otherwise.
	 */
	private function is_incorect_duration( $duration ): bool {
		return ( ! is_numeric( $duration ) || 0 >= (int) $duration );
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|WP_Error
	 */
	public function process() {
		$duration = HTTP::post( 'duration' );

		if ( $this->is_incorect_duration( $duration ) ) {
			$this->resolved = false;

			return new WP_Error(
				'defender_invalid_duration',
				esc_html__( 'Duration can only be a number and greater than 0.', 'wpdef' )
			);
		}
		$this->resolved = true;
		// Sync with the Session feature.
		$session_settings                 = wd_di()->get( \WP_Defender\Model\Setting\Session_Protection::class );
		$session_settings->login_duration = (int) $duration;
		$session_settings->save();

		return update_site_option( self::OPTION_NAME, $duration );
	}

	/**
	 * This is for un-do stuff that were done in @process.
	 *
	 * @return bool
	 */
	public function revert(): bool {
		return delete_site_option( self::OPTION_NAME );
	}

	/**
	 * Set $duration as resolved if value > 0 otherwise revert value.
	 */
	public function shield_up() {
		$duration = $this->get_duration_in_days();
		if ( empty( $duration ) || $this->is_incorect_duration( $duration ) ) {
			$this->resolved = false;

			return $this->revert();
		}
		$this->resolved = true;
		add_filter( 'auth_cookie_expiration', array( $this, 'cookie_duration' ), 10, 3 );
		// Todo: need hook 'login_message'?
	}

	/**
	 * Cookie duration in days in seconds.
	 *
	 * @param  int  $duration  Default duration.
	 * @param  int  $user_id  Current user id.
	 * @param  bool $remember  Remember me login.
	 *
	 * @return int
	 */
	public function cookie_duration( $duration, $user_id, $remember ) {
		$saved_duration = $this->get_tweak_duration();

		// When remember is set or saved_duration is smaller than 2 days, return 1 day in seconds.
		if ( $remember || 2 > $saved_duration ) {
			return $saved_duration * DAY_IN_SECONDS;
		}

		return $duration;
	}

	/**
	 * This will define a value for duration, use in bulk resolve.
	 */
	public function bulk_process() {
		$duration = 7;
		update_site_option( self::OPTION_NAME, $duration );
	}

	/**
	 * Get duration in days.
	 *
	 * @return int
	 */
	private function get_duration_in_days(): int {
		return (int) apply_filters(
			"defender_security_tweaks_{$this->slug}_get_duration",
			get_site_option( self::OPTION_NAME )
		);
	}

	/**
	 * Get duration in days.
	 *
	 * @return int
	 */
	public function get_tweak_duration(): int {
		$duration = $this->get_duration_in_days();
		if ( empty( $duration ) || $this->is_incorect_duration( $duration ) ) {
			return self::DEFAULT_DAYS;
		}

		return $duration;
	}

	/**
	 * Update duration.
	 *
	 * @param int $duration The duration.
	 */
	public function update_tweak_duration( int $duration ) {
		if ( empty( $duration ) || $this->is_incorect_duration( $duration ) ) {
			return;
		}

		update_site_option( self::OPTION_NAME, $duration );
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Manage Login Duration', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		return sprintf(
			/* translators: %d: Number of days. */
			esc_html__( 'Your current login duration is the default %d days.', 'wpdef' ),
			self::DEFAULT_DAYS
		);
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$duration = $this->get_tweak_duration();

		return array(
			'slug'             => $this->slug,
			'title'            => $this->get_label(),
			'errorReason'      => $this->get_error_reason(),
			'successReason'    => sprintf(
			/* translators: %d: Number of days. */
				esc_html__( 'You\'ve adjusted the default login duration to %d days.', 'wpdef' ),
				$duration
			),
			'misc'             => array( 'duration' => $duration ),
			'bulk_description' => esc_html__(
				'Users who select the \'remember me\' option will stay logged in for 14 days.It’s good practice to reduce this default time to reduce the risk of someone gaining access to your automatically logged in account. We’ll set the login duration to 7 days.',
				'wpdef'
			),
			'bulk_title'       => esc_html__( 'Login Duration', 'wpdef' ),
		);
	}
}