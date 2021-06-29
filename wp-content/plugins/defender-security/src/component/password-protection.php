<?php

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;

/**
 * Doing the logic for mask login module
 *
 * Class Password_Protection
 *
 * @package WP_Defender\Component
 */
class Password_Protection extends Component {

	/**
	 * The Pwned API URL
	 * API source website: http://haveibeenpwned.com/
	 * API version: v3
	 * @var string
	 */
	protected $pwned_api;
	/**
	 * @var \WP_Defender\Model\Setting\Password_Reset
	 */
	protected $model;

	public function __construct() {
		$this->pwned_api = 'https://api.pwnedpasswords.com/range/';
		$this->model     = wd_di()->get( \WP_Defender\Model\Setting\Password_Reset::class );
	}

	/**
	 * Get the password that was submitted by user
	 *
	 * @return string $password
	 */
	public function get_submitted_password() {
		$password = '';
		foreach ( array( 'pwd', 'pass1', 'password', 'edd_user_pass' ) as $key ) {
			$submitted_pass = HTTP::post( $key );
			if ( ! empty( $submitted_pass ) ) {
				$password = $submitted_pass;
				break;
			}
		}

		return $password;
	}

	/**
	 * Makes an API request to the remote server and
	 * Checks if the password is pwned
	 *
	 * @param string $password
	 *
	 * @return \WP_Error|bool
	 */
	public function check_pwned_password( $password ) {
		$hash    = strtoupper( hash( 'sha1', $password ) );
		$subhash = substr( $hash, 0, 5 );

		$url  = $this->pwned_api . $subhash;
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'user-agent' => sprintf(
					'Mozilla/5.0 (compatible; WPMU DEV Defender/%1$s; +https://wpmudev.com)',
					DEFENDER_VERSION
				),
			),
		);

		$request = wp_remote_request( $url, $args );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$pwned_count = 0;
		$body        = wp_remote_retrieve_body( $request );
		foreach ( array_map( 'trim', explode( "\n", trim( $body ) ) ) as $row ) {
			if ( $subhash . substr( strtoupper( $row ), 0, 35 ) === $hash ) {
				$pwned_count = substr( $row, 36 );
				break;
			}
		}
		// Found Pwned password or not
		return $pwned_count > 0;
	}

	/**
	 * Check if the specified user role is enabled

	 * @param WP_User $user
	 * @param array $selected_user_roles
	 *
	 * @return bool
	 */
	public function is_enabled_by_user_role( $user, $selected_user_roles ) {
		if ( empty( $user->roles ) ) {
			return false;
		}
		//No for super admin
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return false;
		}

		return ! empty( array_intersect( $selected_user_roles, $user->roles ) );
	}

	/**
	 * Get reset password redirect URL
	 *
	 * @param WP_User $user
	 *
	 * @return string|null
	 */
	public function get_reset_password_redirect_url( $user ) {
		$url = null;

		$key = get_password_reset_key( $user );
		if ( ! is_wp_error( $key ) ) {
			$url = add_query_arg(
				array(
					'action' => 'rp',
					'key'    => $key,
					'login'  => $user->user_login,
				),
				wp_login_url()
			);
		}

		return $url;
	}

	/**
	 * Reset password redirect
	 *
	 * @param string $url
	 * @param bool $safe
	 */
	public function reset_password_redirect( $url, $safe = false ) {
		$url = esc_url_raw( $url );
		header( 'Cache-Control: no-store, no-cache' );
		$safe ? wp_safe_redirect( $url ) : wp_redirect( $url );
		exit();
	}

	/**
	 * Set cookie notice
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $time
	 */
	public function set_cookie_notice( $name, $value, $time ) {
		if ( ! isset( $_COOKIE[ $name ] ) ) {
			setcookie( $name, $value, $time, '/' );
		}
	}

	/**
	 * Remove cookie notice
	 *
	 * @param string $name
	 * @param int $time
	 */
	public function remove_cookie_notice( $name, $time ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			setcookie( $name, null, $time, '/' );
		}
	}

	/**
	 * Get the time when the user's password has last been changed.
	 *
	 * @param WP_User|int $user
	 *
	 * @return int
	 */
	protected function password_last_changed( $user ) {
		if ( ! $user ) {
			return 0;
		}

		$changed = (int) get_user_meta( $user->ID, 'wd_last_password_change', true );

		if ( ! $changed ) {
			return strtotime( $user->user_registered );
		}

		return $changed;
	}

	/**
	 * Is user password expired?
	 *
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function check_expired_password( $user ) {

		return isset( $this->model->force_time ) && $this->model->force_time >= $this->password_last_changed( $user );
	}

	/**
	 * Set the last updated time when a password is updated.
	 *
	 * @param WP_User $user
	 */
	public function handle_password_updated( $user ) {
		update_user_meta( $user->ID, 'wd_last_password_change', time() );
	}
}
