<?php

namespace WP_Defender\Component\Security_Tweaks;

use WP_Error;
use Calotes\Helper\HTTP;
use Calotes\Base\Component;

/**
 * Class Login_Duration
 * @package WP_Defender\Component\Security_Tweaks
 */
class Login_Duration extends Component {
	const DEFAULT_DAYS = 14;
	public $slug = 'login-duration';
	public $resolved = false;

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->resolved;
	}

	/**
	 * @param string|int $duration
	 *
	 * @return bool
	 */
	private function is_incorect_duration( $duration ) {
		return ( ! is_numeric( $duration ) || 0 >= (int) $duration );
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		$duration = HTTP::post( 'duration' );

		if ( $this->is_incorect_duration( $duration ) ) {
			return new WP_Error(
				'defender_invalid_duration',
				__( 'Duration can only be a number and greater than 0', 'wpdef' )
			);
		}

		return update_site_option( "defender_security_tweeks_{$this->slug}", $duration );
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		return delete_site_option( "defender_security_tweeks_{$this->slug}" );
	}

	/**
	 * Set $duration as resolved if value > 0 otherwise revert value.
	 */
	public function shield_up() {
		$duration = get_site_option( "defender_security_tweeks_{$this->slug}" );
		if ( empty( $duration ) || $this->is_incorect_duration( $duration ) ) {
			return $this->revert();
		}
		$this->resolved = true;
		add_filter( 'auth_cookie_expiration', [ $this, 'cookie_duration' ], 10, 3 );
		//Todo: need hook 'login_message'?
	}

	/**
	 * Cookie duration in days in seconds.
	 *
	 * @param int  $duration Default duration.
	 * @param int  $user_id  Current user id.
	 * @param bool $remember Remember me login.
	 *
	 * @return int
	 */
	public function cookie_duration( $duration, $user_id, $remember ) {
		$saved_duration = $this->get_duration( true );

		// When remember is set or saved_duration is smaller than 2 days, return saved_duration.
		if ( $remember || 2 > $saved_duration ) {
			return $saved_duration;
		}

		return $duration;
	}

	/**
	 * This will define a value for duration, use in bulk resolve.
	 */
	public function bulk_process() {
		$duration = 7;
		update_site_option( "defender_security_tweeks_{$this->slug}", $duration );
	}

	/**
	 * Get duration in days or seconds. Returns in seconds on passing true.
	 *
	 * @param bool $in_seconds Default value: false.
	 *
	 * @return int
	 */
	private function get_duration( $in_seconds = false ) {
		$duration = apply_filters( "defender_security_tweaks_{$this->slug}_get_duration",
			get_site_option( "defender_security_tweeks_{$this->slug}" ), $in_seconds );

		if ( ! $in_seconds ) {
			return $duration;
		}

		return $in_seconds ? $duration * DAY_IN_SECONDS : $duration;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Manage Login Duration', 'wpdef' ),
			'errorReason'      => sprintf( __( 'Your current login duration is the default %d days', 'wpdef' ),
				self::DEFAULT_DAYS ),
			'successReason'    => sprintf( __( 'You\'ve adjusted the default login duration to %d days', 'wpdef' ),
				$this->get_duration() ),
			'misc'             => [
				'duration' => $this->get_duration()
			],
			'bulk_description' => __( 'Users who select the \'remember me\' option will stay logged in for 14 days.It’s good practice to reduce this default time to reduce the risk of someone gaining access to your automatically logged in account. We’ll set the login duration to 7 days.',
				'wpdef' ),
			'bulk_title'       => __( 'Login Duration', 'wpdef' )
		];
	}
}
