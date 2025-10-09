<?php
/**
 * Handles session protection functionality within the WP Defender plugin.
 *
 * @package WP_Defender\Component
 * @since   5.2.0
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Traits\IP;
use WP_Defender\Traits\User;
use WP_User_Meta_Session_Tokens;
use WP_Defender\Model\Setting\Session_Protection as Settings;

/**
 * This class handles session protection functionality within the WP Defender plugin.
 */
class Session_Protection extends Component {
	use IP;
	use User;

	/**
	 * Key to lock the session to the browser.
	 */
	const LOCK_BROWSER = 'browser';

	/**
	 * Key to lock the session to the hostname.
	 */
	const LOCK_HOSTNAME = 'hostname';

	/**
	 * Key to lock the session to the IP address.
	 */
	const LOCK_IP_ADDRESS = 'ip_address';

	/**
	 * The name of the log file.
	 *
	 * @var string
	 */
	const LOG_FILE_NAME = 'session-protection';

	/**
	 * Transient key to show custom logout message in the login modal.
	 *
	 * @var string
	 */
	const LOGOUT_MSG_TRANSIENT_KEY = 'wpdef_session_logout_msg';

	/**
	 * The session protection settings.
	 *
	 * @var Settings|null
	 */
	protected ?Settings $settings;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->settings = wd_di()->get( Settings::class );
	}

	/**
	 * Retrieve an array of default session lock properties.
	 *
	 * @return array An array of session lock properties.
	 */
	public static function session_lock_properties(): array {
		return array(
			self::LOCK_BROWSER    => esc_html__( 'Browser', 'wpdef' ),
			self::LOCK_HOSTNAME   => esc_html__( 'Hostname', 'wpdef' ),
			self::LOCK_IP_ADDRESS => esc_html__( 'IP Address', 'wpdef' ),
		);
	}

	/**
	 * Prepare details of the Session Lock property for MP event.
	 * No need to translate.
	 *
	 * @return string
	 */
	public function get_session_lock_string(): string {
		if ( ! $this->settings->has_properties() ) {
			return 'Not Set';
		}
		$checked_properties = array();
		if ( $this->settings->is_property_locked( self::LOCK_IP_ADDRESS ) ) {
			$checked_properties[] = 'IP';
		}
		if ( $this->settings->is_property_locked( self::LOCK_HOSTNAME ) ) {
			$checked_properties[] = 'Hostname';
		}
		if ( $this->settings->is_property_locked( self::LOCK_BROWSER ) ) {
			$checked_properties[] = 'Browser';
		}

		return implode( '/', $checked_properties );
	}

	/**
	 * Handles session timeout logic on init hook.
	 *
	 * @return void
	 */
	public function handle_session_timeout() {
		if (
			! is_user_logged_in()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| defender_is_rest_api_request()
			|| $this->request_is_from_server()
		) {
			return;
		}

		if ( ! $this->can_apply_session_protection() ) {
			return;
		}

		if ( $this->settings->has_properties() ) {
			$this->handle_session_lock();
			return;
		}

		$idle_timeout  = $this->get_idle_timeout();
		$last_activity = $this->get_last_activity();

		if ( $last_activity && ( defender_get_current_time() - $last_activity > $idle_timeout ) ) {
			$this->log(
				sprintf( 'User session timed out due to inactivity during %s hours', $this->settings->idle_timeout ),
				self::LOG_FILE_NAME
			);
			$user_id = get_current_user_id();
			// Fires during session timeout.
			do_action( 'wpdef_session_timeout', $user_id );
			$this->logout( $user_id );

			return;
		}
		$this->update_last_activity();
	}

	/**
	 * Retrieves the last activity timestamp.
	 *
	 * @return int|null Last activity timestamp or null if not set.
	 */
	private function get_last_activity() {
		// Check for client-side cookie first to avoid unnecessary database queries.
		if ( isset( $_COOKIE['wpdef_last_activity'] ) && is_numeric( $_COOKIE['wpdef_last_activity'] ) ) {
			return (int) $_COOKIE['wpdef_last_activity'];
		}

		return null;
	}

	/**
	 * Logs out the current user due to inactivity.
	 *
	 * @param int|null $user_id User ID.
	 *
	 * @return void
	 */
	public function logout( $user_id = null ) {
		if ( is_user_logged_in() ) {
			// Case without $user_id may be from Ajax.
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
				$this->log(
					sprintf( 'User session timed out due to inactivity during %s hours', $this->settings->idle_timeout ),
					self::LOG_FILE_NAME
				);
				// Fires during session timeout.
				do_action( 'wpdef_session_timeout', $user_id );
			}
			// Get current session token.
			$current_session_token = wp_get_session_token();
			if ( $current_session_token ) {
				// Get session manager for the user.
				$session_manager = \WP_Session_Tokens::get_instance( $user_id );

				// Destroy ONLY the current session.
				$session_manager->destroy( $current_session_token );
			} else {
				$manager = \WP_Session_Tokens::get_instance( $user_id );
				$manager->destroy_all();
			}

			// Reset last activity cookie.
			setcookie( 'wpdef_last_activity', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

			// Set transient to show custom message in the login modal.
			set_site_transient( self::LOGOUT_MSG_TRANSIENT_KEY, 1, 30 );

			// Redirect to login page after session destruction.
			wp_safe_redirect( wp_login_url() );
		}
	}

	/**
	 * Show custom message in the login modal when session is expired.
	 *
	 * @param \WP_Error $errors WP Error object.
	 *
	 * @return \WP_Error
	 */
	public function login_modal_message( $errors ) {
		if (
			empty( defender_get_data_from_request( 'interim-login', 'g' ) ) ||
			! get_site_transient( self::LOGOUT_MSG_TRANSIENT_KEY )
		) {
			return $errors;
		}

		delete_site_transient( self::LOGOUT_MSG_TRANSIENT_KEY );

		if ( ! ( $errors instanceof \WP_Error ) ) {
			return $errors;
		}

		$error_keys = array( 'expired', 'expired_session' );
		foreach ( $error_keys as $key ) {
			if ( $errors->get_error_message( $key ) ) {
				$errors->remove( $key );
				$errors->add(
					$key,
					__( 'You\'ve been logged out due to inactivity. Please sign in to continue.', 'wpdef' ),
					'message'
				);

				break;
			}
		}

		return $errors;
	}

	/**
	 * Styles for the custom message in the login modal.
	 *
	 * @return void
	 */
	public function login_modal_message_styles(): void {
		if ( get_site_transient( self::LOGOUT_MSG_TRANSIENT_KEY ) ) {
			// Don't delete the transient here, it will be deleted in the login_modal_message() method.

			echo '<style>
				#login_error {
					border-left-color: #72aee6;
				}
			</style>';
		}
	}

	/**
	 * Enqueues JavaScript for client-side idle detection.
	 *
	 * @return void
	 */
	public function enqueue_idle_scripts() {
		if ( is_user_logged_in() && $this->can_apply_session_protection() ) {
			wp_enqueue_script( 'wpdef-session-idle', plugins_url( 'assets/js/idle-session.js', WP_DEFENDER_FILE ), array( 'jquery' ), DEFENDER_VERSION, true );
			wp_localize_script(
				'wpdef-session-idle',
				'wpdef_idle_params',
				array(
					'timeout'   => $this->get_idle_timeout(),
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'login_url' => wp_login_url(),
					// Exclude sending Logout-callbacks if the user is logged out and the login form is displayed in the popup.
					'stop'      => false,
				)
			);
		}
	}

	/**
	 * Retrieves the idle timeout duration.
	 *
	 * @return int The idle timeout duration in seconds.
	 */
	public function get_idle_timeout() {
		/**
		 * Filter the idle timeout duration in seconds.
		 *
		 * @param int $idle_timeout The idle timeout duration in seconds.
		 *
		 * @return int The idle timeout duration in seconds.
		 */
		return (int) apply_filters( 'wpdef_idle_timeout', $this->settings->idle_timeout * HOUR_IN_SECONDS );
	}

	/**
	 * Updates the last activity timestamp efficiently.
	 *
	 * @return void
	 */
	public function update_last_activity() {
		$current_time  = time();
		$last_activity = $this->get_last_activity();

		// Only update the cookie if minutes have passed since the last update.
		if ( ! empty( $last_activity ) || ( $current_time - $last_activity > MINUTE_IN_SECONDS ) ) {
			setcookie( 'wpdef_last_activity', $current_time, time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Attach session information to the current user session.
	 *
	 * @param array $session Array of extra data.
	 *
	 * @return array Array of extra data.
	 */
	public function attach_session_information( $session ) {
		if ( $this->settings->is_property_locked( self::LOCK_IP_ADDRESS ) ) {
			$session['defender_ip'] = implode( '|', $this->get_user_ip() );
		}

		if ( $this->settings->is_property_locked( self::LOCK_HOSTNAME ) ) {
			$session['defender_hostname'] = defender_get_domain();
		}

		if ( $this->settings->is_property_locked( self::LOCK_BROWSER ) ) {
			$session['defender_browser'] = defender_get_user_agent();
		}

		return $session;
	}

	/**
	 * Handles session lock logic.
	 *
	 * @return void
	 */
	public function handle_session_lock() {
		$token        = wp_get_session_token();
		$user_id      = get_current_user_id();
		$manager      = WP_User_Meta_Session_Tokens::get_instance( $user_id );
		$session_data = $manager->get( $token );
		if ( ! is_array( $session_data ) ) {
			$this->log( 'Session data is missing or malformed. Destroying all sessions.', self::LOG_FILE_NAME );
			$this->logout( $user_id );
			return;
		}
		$user_ips = $this->get_user_ip();
		// Exclude Loopback case. If condition returns true, then there is no Loopback intersection.
		if ( ! empty( array_intersect( $user_ips, $this->get_localhost_ips() ) ) ) {
			return;
		}
		// Work with the Session Lock options.
		if ( $this->settings->is_property_locked( self::LOCK_IP_ADDRESS ) && isset( $session_data['defender_ip'] ) ) {
			$ips = implode( '|', $user_ips );
			$this->is_ip_allowed( $ips, $session_data['defender_ip'], $user_id );
		}

		if ( $this->settings->is_property_locked( self::LOCK_HOSTNAME ) && isset( $session_data['defender_hostname'] ) ) {
			$hostname = defender_get_domain();
			$this->is_hostname_allowed( $hostname, $session_data['defender_hostname'], $user_id );
		}

		if ( $this->settings->is_property_locked( self::LOCK_BROWSER ) && isset( $session_data['defender_browser'] ) ) {
			$browser = defender_get_user_agent();
			$this->is_browser_allowed( $browser, $session_data['defender_browser'], $user_id );
		}
	}

	/**
	 * Verifies if the given IP is allowed based on the session information.
	 *
	 * @param string $ip IP addresses to check.
	 * @param string $session_ip Session IP address.
	 * @param int    $user_id User ID.
	 *
	 * @return void
	 */
	private function is_ip_allowed( $ip, $session_ip, $user_id ) {
		if ( empty( $ip ) || empty( $session_ip ) || $ip !== $session_ip ) {
			$this->log(
				sprintf(
					'IP address mismatch detected. Detected: %s, Expected: %s.',
					$ip,
					$session_ip
				),
				self::LOG_FILE_NAME
			);
			/**
			 * Fires during session lock.
			 *
			 * @param int    $user_id
			 * @param string $session_lock_type
			 */
			do_action( 'wpdef_session_lock', $user_id, 'IP address' );
			$this->logout( $user_id );
		}
	}

	/**
	 * Verifies if the given hostname is allowed based on the session information.
	 *
	 * @param string $hostname Hostname to check.
	 * @param string $session_hostname Session hostname.
	 * @param int    $user_id User ID.
	 *
	 * @return void
	 */
	private function is_hostname_allowed( $hostname, $session_hostname, $user_id ) {
		if ( empty( $hostname ) || empty( $session_hostname ) || strcasecmp( $hostname, $session_hostname ) !== 0 ) {
			$this->log(
				sprintf(
					'Hostname mismatch detected. Detected: %s, Expected: %s.',
					$hostname,
					$session_hostname
				),
				self::LOG_FILE_NAME
			);
			do_action( 'wpdef_session_lock', $user_id, 'hostname' );
			$this->logout( $user_id );
		}
	}

	/**
	 * Verifies if the given browser is allowed based on the session information.
	 *
	 * @param string $browser Browser to check.
	 * @param string $session_browser Session browser.
	 * @param int    $user_id User ID.
	 *
	 * @return void
	 */
	private function is_browser_allowed( $browser, $session_browser, $user_id ) {
		if ( empty( $browser ) || empty( $session_browser ) || strcasecmp( $browser, $session_browser ) !== 0 ) {
			$this->log(
				sprintf(
					'Browser mismatch detected. Detected: %s, Expected: %s.',
					$browser,
					$session_browser
				),
				self::LOG_FILE_NAME
			);
			do_action( 'wpdef_session_lock', $user_id, 'browser' );
			$this->logout( $user_id );
		}
	}

	/**
	 * Should we apply this feature for the current user?
	 *
	 * @return bool
	 */
	public function can_apply_session_protection(): bool {
		$current_user = $this->get_user( get_current_user_id() );

		/**
		 * Filter whether to apply the feature for the current user.
		 *
		 * @param bool     $apply         Whether to apply the feature.
		 * @param \WP_User $current_user  The current user object.
		 *
		 * @return bool
		 */
		return (bool) apply_filters( 'wpdef_toggle_session_protection', $this->should_enforce_for_user( $current_user ), $current_user );
	}
}