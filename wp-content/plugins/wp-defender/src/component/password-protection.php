<?php

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;
use WP_User;

/**
 * Doing the logic for Pwned Passwords module.
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

	/**
	 * Use for cache
	 *
	 * @var \WP_Defender\Model\Setting\Password_Protection
	 */
	protected $password_protection_model;

	public function __construct() {
		$this->pwned_api = 'https://api.pwnedpasswords.com/range/';
		$this->model     = wd_di()->get( \WP_Defender\Model\Setting\Password_Reset::class );

		$this->password_protection_model = wd_di()->get(
			\WP_Defender\Model\Setting\Password_Protection::class
		);
	}

	/**
	 * Get the password that was submitted by user.
	 *
	 * @return string
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
	 * Checks if the password is pwned.
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
	 * Check if the specified user role is enabled.

	 * @param WP_User $user
	 * @param array $selected_user_roles
	 *
	 * @return bool
	 */
	public function is_enabled_by_user_role( $user, $selected_user_roles ) {
		// No for super admin.
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return false;
		}

		if ( empty( $user->roles ) ) {
			$user_id = $user->ID;
			if ( ! is_multisite() ) {
				// User should have roles.
				$this->log( sprintf( "User ID: %d doesn't have roles", $user_id ), 'password.log' );
				return false;
			} else {
				$arr_user_blogs = get_blogs_of_user( $user_id );
				if ( empty( $arr_user_blogs ) ) {
					// User should be associated with some site.
					$this->log( sprintf( 'User ID: %d is not associated with any site', $user_id ), 'password.log' );
					return false;
				}
				$user_blog_id = array_key_first( $arr_user_blogs );
				$user         = new WP_User( $user_id, '', $user_blog_id );
			}
		}
		$user_roles = $user->roles;

		return ! empty( array_intersect( $selected_user_roles, $user_roles ) );
	}

	/**
	 * Get reset password redirect URL.
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
			// extra hosting checks
			$this->hosting_compatibility( $key, $user->user_login );
		}

		return $url;
	}

	/**
	 * Reset password redirect.
	 *
	 * @param string|null $url
	 * @param bool        $safe
	 *
	 * @return void
	 */
	public function reset_password_redirect( $url, $safe = false ) {
		if ( empty( $url ) ) {
			return;
		}
		$url = esc_url_raw( $url );
		header( 'Cache-Control: no-store, no-cache' );
		$safe ? wp_safe_redirect( $url ) : wp_redirect( $url );
		exit();
	}

	/**
	 * Set cookie notice.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int    $time
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
		$this->remove_extra_cookies();
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

	/**
	 * @param string $key
	 * @param string $user_login
	 *
	 * @since 2.5.5
	 */
	protected function hosting_compatibility( $key, $user_login ) {
		$mask_login = new \WP_Defender\Model\Setting\Mask_Login();
		if (
			! isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] )
			&& $mask_login->is_active()
			&& 'flywheel' === \WP_Defender\Component\Security_Tweaks\Servers\Server::get_current_server()
		) {
			$value = sprintf( '%s:%s', $user_login, $key );
			setcookie( 'wp-resetpass-' . COOKIEHASH, $value, 0, $mask_login->get_new_login_url(), COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * @since 2.5.5
	 */
	protected function remove_extra_cookies() {
		if (
			isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] )
			&& 'flywheel' === \WP_Defender\Component\Security_Tweaks\Servers\Server::get_current_server()
		) {
			setcookie( 'wp-resetpass-' . COOKIEHASH, '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Force reset password.
	 *
	 * @param \WP_User|\WP_Error $user     WP_User object or WP_Error.
	 * @param string             $password Password plain string.
	 *
	 * @return \WP_User|\WP_Error Return user object or error object.
	 */
	public function do_force_reset( $user, $password ) {
		if (
			! is_wp_error( $user ) &&
			wp_check_password( $password, $user->user_pass, $user->ID ) &&
			$this->is_force_reset( $user )
		) {
			$action      = 'password_reset';
			$cookie_name = 'display_reset_password_warning';

			$this->trigger_redirect( $user, $action, $cookie_name );
		}

		return $user;
	}

	/**
	 * Reset weak password.
	 *
	 * @param \WP_User|\WP_Error $user     WP_User object or WP_Error.
	 * @param string             $password Password plain string.
	 *
	 * @return \WP_User|\WP_Error Return user object or error object.
	 */
	public function do_weak_reset( $user, $password ) {
		if (
			! is_wp_error( $user ) &&
			wp_check_password( $password, $user->user_pass, $user->ID ) &&
			$this->is_weak_password( $user, $password )
		) {
			$action      = 'password_protection';
			$cookie_name = 'display_pwned_password_warning';

			$this->trigger_redirect( $user, $action, $cookie_name );
		}

		return $user;
	}

	/**
	 * Redirect to reset password with error message
	 *
	 * @param \WP_User|\WP_Error $user        WP_User object or WP_Error.
	 * @param string             $action      Action query string name.
	 * @param string             $cookie_name Cookie name.
	 */
	private function trigger_redirect( $user, $action, $cookie_name ) {
		// Set cookie to check and display the warning notice on reset password page.
		$this->set_cookie_notice(
			$cookie_name,
			true,
			time() + MINUTE_IN_SECONDS * 2
		);
		// Get the reset password URL.
		$url = $this->get_reset_password_redirect_url( $user );
		/**
		 * Fires before redirecting to the password reset page.
		 *
		 * @since 2.5.6
		 *
		 * @param string $url
		 * @param string $action
		 */
		do_action( 'wd_forced_reset_password_url', $url, $action );
		// Redirect to the reset password page.
		$this->reset_password_redirect( $url );
	}

	/**
	 * Verify if password is weak.
	 *
	 * Is user role enabled for weak password verification and is the password weak?
	 * If so then return true else password is strong therefore return false.
	 *
	 * @since 2.6.1
	 *
	 * @param WP_User $user     WP_User object.
	 * @param string  $password Plain password string.
	 *
	 * @return bool If password weak then true else false.
	 */
	public function is_weak_password( $user, $password ) {
		$user_roles         = $this->password_protection_model->user_roles;
		$is_enabled_by_user = $this->is_enabled_by_user_role( $user, $user_roles );
		$is_pwned           = $this->check_pwned_password( $password );

		$is_weak_password = $this->password_protection_model->is_active() &&
			$is_enabled_by_user &&
			! is_wp_error( $is_pwned ) &&
			$is_pwned;

		return $is_weak_password;
	}

	/**
	 * Verify if password is need to be force reset.
	 *
	 * Is user role enabled for force password reset and is the password expired?
	 * If so then return true else password is non expired therefore return false.
	 *
	 * @since 2.6.1
	 *
	 * @param WP_User $user     WP_User object.
	 *
	 * @return bool If password expired then true else false.
	 */
	public function is_force_reset( $user ) {
		$user_roles         = $this->model->user_roles;
		$is_enabled_by_user = $this->is_enabled_by_user_role( $user, $user_roles );
		$is_expired         = $this->check_expired_password( $user );

		$is_expired_password = $this->model->is_active() &&
			$is_enabled_by_user &&
			$is_expired;

		return $is_expired_password;
	}
}