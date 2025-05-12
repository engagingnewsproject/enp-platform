<?php
/**
 * Handles all operations related to two-factor authentication (2FA) for users.
 *
 * @package    WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_User;
use WP_Error;
use Countable;
use WP_User_Query;
use SodiumException;
use WP_Defender\Traits\IP;
use Calotes\Base\Component;
use WP_Defender\Traits\User;
use Calotes\Helper\Array_Cache;
use WP_Defender\Controller\Two_Factor;
use WordfenceLS\Controller_WordfenceLS;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Component\Two_Factor\Providers\Totp;
use WP_Defender\Model\Setting\Two_Fa as Two_Fa_Model;
use WP_Defender\Component\Two_Factor\Providers\Webauthn;
use WP_Defender\Component\Two_Factor\Providers\Backup_Codes;
use WP_Defender\Component\Two_Factor\Providers\Fallback_Email;

/**
 * Handles all operations related to two-factor authentication (2FA) for users.
 */
class Two_Fa extends Component {

	use User;
	use IP;

	/**
	 * The user meta key for the default provider.
	 *
	 * @var string
	 */
	public const DEFAULT_PROVIDER_USER_KEY = 'wd_2fa_default_provider';

	/**
	 * The user meta key for enabled providers.
	 *
	 * @var string
	 */
	public const ENABLED_PROVIDERS_USER_KEY = 'wd_2fa_enabled_providers';

	/**
	 * Default limit for failed 2FA attempts.
	 *
	 * @var int
	 */
	public const ATTEMPT_LIMIT = 5;

	/**
	 * Default time limit for 2FA attempt window in seconds.
	 *
	 * @var int
	 */
	public const TIME_LIMIT = 30 * MINUTE_IN_SECONDS;

	/**
	 * User meta key for 2FA tokens.
	 *
	 * @var string
	 */
	public const TOKEN_USER_KEY = 'defender_two_fa_token';

	/**
	 * Get the limit of failed attempts.
	 *
	 * @return int
	 * @since 3.3.0
	 */
	public function get_attempt_limit(): int {
		return (int) apply_filters( 'wd_2fa_attempt_limit', self::ATTEMPT_LIMIT );
	}

	/**
	 * Get the limit of time.
	 *
	 * @return int
	 */
	public function get_time_limit() {
		return (int) apply_filters( 'wd_2fa_time_limit', self::TIME_LIMIT );
	}

	/**
	 * Enable OTP for a user.
	 *
	 * @param  int $user_id  The ID of the user.
	 *
	 * @return void
	 */
	public function enable_otp( int $user_id ): void {
		update_user_meta( $user_id, Totp::TOTP_AUTH_KEY, 1 );
		update_user_meta( $user_id, Totp::TOTP_FORCE_KEY, 0 );
	}

	/**
	 * Gradually move on to using the method is_enabled_otp_for_user(). Used only by the Forminator plugin.
	 *
	 * @param  int $user_id  User ID to check.
	 *
	 * @return mixed
	 */
	public function is_user_enabled_otp( int $user_id ) {
		return get_user_meta( $user_id, Totp::TOTP_AUTH_KEY, true );
	}

	/**
	 * Checks if forced authentication is enabled for specific user roles.
	 *
	 * @param  int   $user_id  The user ID.
	 * @param  array $roles  The user roles to check.
	 *
	 * @return bool True if forced authentication is enabled, false otherwise.
	 */
	public function is_force_auth_enable_for( int $user_id, array $roles ): bool {
		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return false;
		}

		$check = array_intersect( $this->get_roles( $user ), $roles );

		return count( $check ) > 0;
	}

	/**
	 * Count the total of users, who enables any 2FA method.
	 *
	 * @return int
	 */
	public function count_users_with_enabled_2fa(): int {
		$slugs = array_keys( $this->get_providers() );
		$query = new WP_User_Query(
			array(
				// Look over the network.
				'blog_id'    => 0,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::DEFAULT_PROVIDER_USER_KEY,
						'value'   => $slugs,
						'compare' => 'IN',
					),
				),
			)
		);

		return $query->get_total();
	}

	/**
	 * Checks if Jetpack SSO is in conflict with 2FA settings.
	 *
	 * @return bool|int True if there is a conflict, false otherwise, or 0 if undetermined.
	 */
	public function is_jetpack_sso() {
		$settings = new Two_Fa_Model();
		if ( is_plugin_active_for_network( 'jetpack/jetpack.php' ) ) {
			// Loop through all sites.
			$is_conflict = $settings->is_conflict( 'jetpack/jetpack.php' );
			if ( 0 === $is_conflict ) {
				// No data, init.
				global $wpdb;
				$blogs = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					"SELECT blog_id FROM `{$wpdb->base_prefix}blogs`"
				);
				foreach ( $blogs as $id ) {
					$options = get_blog_option( $id, 'jetpack_active_modules', array() );
					if ( array_search( 'sso', $options, true ) ) {
						$settings->mark_as_conflict( 'jetpack/jetpack.php' );

						return true;
					}
				}
			} else {
				// Get the data from cache.
				return $is_conflict;
			}
		} elseif ( is_plugin_active( 'jetpack/jetpack.php' ) ) {
			$is_conflict = $settings->is_conflict( 'jetpack/jetpack.php' );
			if ( 0 === $is_conflict ) {
				$options = get_option( 'jetpack_active_modules', array() );
				if ( array_search( 'sso', $options, true ) ) {
					$settings->mark_as_conflict( 'jetpack/jetpack.php' );

					return true;
				}
			} else {
				return $is_conflict;
			}
		}

		return false;
	}

	/**
	 * Checks if Theme My Login plugin is active and marks it as a conflict.
	 *
	 * @return bool True if Theme My Login is active, false otherwise.
	 */
	public function is_tml(): bool {
		if (
			is_plugin_active( 'theme-my-login/theme-my-login.php' )
			|| is_plugin_active_for_network( 'theme-my-login/theme-my-login.php' )
		) {
			$settings = new Two_Fa_Model();
			$settings->mark_as_conflict( 'theme-my-login/theme-my-login.php' );

			return true;
		}

		return false;
	}

	/**
	 * Retrieves the custom graphic URL for 2FA, with fallback to a default image if necessary.
	 *
	 * @param  string $url  Optional. The custom URL to validate.
	 *
	 * @return string The URL to the graphic.
	 */
	public function get_custom_graphic_url( string $url = '' ): string {
		if ( empty( $url ) ) {
			// Nothing here, surely it will cause broken, fall back to default.
			return defender_asset_url( '/assets/img/2factor-disabled.svg' );
		} else {
			// Image should be under wp-content/.., so we catch that part.
			if ( preg_match( '/(\/wp-content\/.+)/', $url, $matches ) ) {
				$rel_path = $matches[1];
				$rel_path = ltrim( $rel_path, '/' );
				$abs_path = ABSPATH . $rel_path;
				if ( ! file_exists( $abs_path ) ) {
					// Fallback.
					return defender_asset_url( '/assets/img/2factor-disabled.svg' );
				} else {
					// Should replace with our site url.
					return get_site_url( null, $rel_path );
				}
			}

			return defender_asset_url( '/assets/img/2factor-disabled.svg' );
		}
	}

	/**
	 * Checks if 2FA is enabled for the current role of the specified user.
	 *
	 * @param  WP_User $user  The user to check.
	 *
	 * @return bool True if 2FA is enabled for the user's role, false otherwise.
	 */
	public function is_enable_for_current_role( WP_User $user ): bool {
		if ( 0 === ( is_array( $user->roles ) || $user->roles instanceof Countable ? count( $user->roles ) : 0 ) ) {
			return true;
		}

		$settings = new Two_Fa_Model();
		if ( ! is_multisite() ) {
			$allowed_for_this_role = array_intersect( $settings->user_roles, $user->roles );
			if ( ! is_array( $allowed_for_this_role ) ) {
				$allowed_for_this_role = array();
			}

			return count( $allowed_for_this_role ) > 0;
		} else {
			$blogs      = get_blogs_of_user( $user->ID );
			$user_roles = array();
			foreach ( $blogs as $blog ) {
				// Get user roles for this blog.
				$u          = new WP_User( $user->ID, '', $blog->userblog_id );
				$user_roles = array_merge( $u->roles, $user_roles );
			}
			$allowed_for_this_role = array_intersect( $settings->user_roles, $user_roles );

			return count( $allowed_for_this_role ) > 0;
		}
	}

	/**
	 * Checks if OTP is enabled for a specific user.
	 *
	 * @param  int|WP_User $user_value  The user ID or WP_User object.
	 *
	 * @return bool True if OTP is enabled, false otherwise.
	 */
	public function is_enabled_otp_for_user( $user_value ): bool {
		if ( is_numeric( $user_value ) ) {
			$user = get_user_by( 'id', $user_value );
		} elseif ( $user_value instanceof WP_User ) {
			$user = $user_value;
		} else {
			return false;
		}

		if ( ! $this->is_enable_for_current_role( $user ) ) {
			return false;
		}

		return (bool) get_user_meta( $user->ID, Totp::TOTP_AUTH_KEY, true );
	}

	/**
	 * Checks if the specified user roles intersect with the plugin's user roles.
	 *
	 * @param  array $current_user_roles  The current user roles.
	 * @param  array $plugin_user_roles  The plugin's user roles.
	 *
	 * @return bool True if there is an intersection, false otherwise.
	 */
	public function is_intersected_arrays( array $current_user_roles, array $plugin_user_roles ): bool {
		return ! empty( array_intersect( $current_user_roles, $plugin_user_roles ) );
	}

	/**
	 * Retrieves all 2FA providers.
	 *
	 * @return array An array of 2FA providers.
	 */
	public function get_providers(): array {
		$providers = Array_Cache::get( 'providers', 'two_fa' );
		if ( ! is_array( $providers ) ) {
			$classes = array(
				Totp::class,
				Backup_Codes::class,
				Fallback_Email::class,
				Webauthn::class,
			);
			/**
			 * Filter the supplied providers.
			 *
			 * @param  array  $classes
			 *
			 * @since 2.8.0
			 */
			$classes = apply_filters( 'wd_2fa_providers', $classes );
			foreach ( $classes as $class ) {
				$providers[ $class::$slug ] = new $class();
			}
			Array_Cache::set( 'providers', $providers, 'two_fa' );
		}

		return $providers;
	}

	/**
	 * Get all 2FA Auth providers that are enabled for the specified|current user.
	 *
	 * @param  WP_User|null $user  The user to check.
	 *
	 * @return array
	 */
	public function get_enabled_providers_for_user( $user = null ): array {
		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$providers         = $this->get_providers();
		$enabled_providers = get_user_meta( $user->ID, self::ENABLED_PROVIDERS_USER_KEY, true );
		if ( empty( $enabled_providers ) ) {
			$enabled_providers = array();
		}
		$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );

		/**
		 * Filter the enabled 2FA providers for this user.
		 *
		 * @param  array  $enabled_providers  The enabled providers.
		 * @param  int  $user_id  The user ID.
		 *
		 * @since 2.8.0
		 */
		return apply_filters( 'wd_2fa_enabled_providers_for_user', $enabled_providers, $user->ID );
	}

	/**
	 * Retrieves all available 2FA providers for the specified or current user.
	 *
	 * @param  WP_User|null $user  The user to check. If null, the current user is used.
	 *
	 * @return array An array of available 2FA providers.
	 */
	public function get_available_providers_for_user( $user = null ): array {
		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$providers            = $this->get_providers();
		$enabled_providers    = $this->get_enabled_providers_for_user( $user );
		$configured_providers = array();

		foreach ( $providers as $slug => $provider ) {
			if ( in_array( $slug, $enabled_providers, true ) && $provider->is_available_for_user( $user ) ) {
				$configured_providers[ $slug ] = $provider;
			}
		}

		return $configured_providers;
	}

	/**
	 * Gets the 2FA provider's slug for the specified or current user.
	 *
	 * @param  int $user_id  Optional. User ID. Default is 'null'.
	 *
	 * @return string|null
	 */
	public function get_default_provider_slug_for_user( $user_id = null ) {
		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$available_providers = $this->get_available_providers_for_user( get_userdata( $user_id ) );
		// If there's only one available provider, force that to be the primary.
		if ( empty( $available_providers ) ) {
			return null;
		} elseif ( 1 === count( $available_providers ) ) {
			$provider_slug = key( $available_providers );
		} else {
			$provider_slug = get_user_meta( $user_id, self::DEFAULT_PROVIDER_USER_KEY, true );

			// If the provider specified isn't enabled, just grab the first one that is.
			if ( ! isset( $available_providers[ $provider_slug ] ) ) {
				$provider_slug = key( $available_providers );
			}
		}

		/**
		 * Filter the 2FA provider slug used for this user.
		 *
		 * @param  string  $provider_slug  The provider slug currently being used.
		 * @param  int  $user_id  The user ID.
		 */
		return apply_filters( 'wd_2fa_default_provider_for_user', $provider_slug, $user_id );
	}

	/**
	 * Checks if a specific 2FA provider is enabled for a user by its slug.
	 *
	 * @param  WP_User $user  The user to check.
	 * @param  string  $slug  The provider slug.
	 *
	 * @return bool True if the provider is enabled, false otherwise.
	 */
	public function is_checked_enabled_provider_by_slug( WP_User $user, string $slug ): bool {
		$enabled_providers = $this->get_enabled_providers_for_user( $user );

		return in_array( $slug, $enabled_providers, true );
	}

	/**
	 * Send emergency email to users.
	 *
	 * @param  string     $login_token  This will be generated randomly on frontend each time user refresh, an internal
	 *                                      OTP.
	 * @param  string|int $user_id  User ID to send email to.
	 *
	 * @return boolean|WP_Error
	 */
	public function send_otp_to_email( string $login_token, $user_id ) {
		if ( empty( $user_id ) || ! is_int( $user_id ) ) {
			return new WP_Error( Error_Code::INVALID, esc_html__( 'The user is invalid.', 'wpdef' ) );
		}
		$settings     = new Two_Fa_Model();
		$hashed_token = get_user_meta( $user_id, self::TOKEN_USER_KEY, true );
		if ( ! Crypt::compare_lines( $hashed_token, wp_hash( $user_id . $login_token ) ) ) {
			return new WP_Error( Error_Code::INVALID, esc_html__( 'Your token is invalid.', 'wpdef' ) );
		}

		$code = wp_generate_password( 20, false );
		update_user_meta(
			$user_id,
			Fallback_Email::FALLBACK_BACKUP_CODE_KEY,
			array(
				'code' => wp_hash( $code ),
				'time' => time(),
			)
		);
		$user   = get_user_by( 'id', $user_id );
		$params = array(
			'display_name' => $user->display_name,
			'passcode'     => $code,
		);
		$two_fa = wd_di()->get( Two_Factor::class );
		$body   = $two_fa->render_partial(
			'email/2fa-lost-phone',
			array(
				'body' => $settings->email_body,
			),
			false
		);

		foreach ( $params as $key => $val ) {
			if ( 'passcode' === $key ) {
				$body = str_replace( "{{{$key}}}", '<span class="defender-otp">' . $val . '</span>', $body );
			} else {
				$body = str_replace( "{{{$key}}}", $val, $body );
			}
		}
		// Main email template.
		$body = $two_fa->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Two-Factor Authentication', 'wpdef' ),
				'content_body'     => $body,
				// An empty value because 2FA-email is sent after a manual click from the user.
				'unsubscribe_link' => '',
			),
			false
		);
		// Since v5.2.0.
		$from_email = defender_noreply_email( 'wd_two_fa_totp_noreply_email' );
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
		$headers[]  = sprintf( 'From: %s <%s>', $settings->email_sender, $from_email );

		return wp_mail( Fallback_Email::get_backup_email( $user->ID ), $settings->email_subject, $body, $headers );
	}

	/**
	 * Retrieves a 2FA provider by its slug.
	 *
	 * @param  string $slug  The provider slug.
	 *
	 * @return object|WP_Error The provider object if found, WP_Error otherwise.
	 */
	public function get_provider_by_slug( $slug ) {
		foreach ( $this->get_providers() as $key => $provider ) {
			if ( $slug === $key ) {
				return $provider;
			}
		}

		return new WP_Error( 'opt_fail', esc_html__( 'ERROR: Cheatin&#8217; uh?', 'wpdef' ) );
	}

	/**
	 * Remove enabled provider for the specified|current user.
	 *
	 * @param  string       $provider  A provider which needs to be removed.
	 * @param  WP_User|null $user  The current user   to be removed.
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function remove_enabled_provider_for_user( string $provider, $user = null ): void {
		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$enabled_providers = get_user_meta( $user->ID, self::ENABLED_PROVIDERS_USER_KEY, true );
		if ( empty( $enabled_providers ) || ! is_array( $enabled_providers ) ) {
			return;
		}

		$pos = array_search( $provider, $enabled_providers, true );
		if ( false !== $pos ) {
			unset( $enabled_providers[ $pos ] );
			update_user_meta( $user->ID, self::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );

			$default_provider = get_user_meta( $user->ID, self::DEFAULT_PROVIDER_USER_KEY, true );
			if ( $provider === $default_provider ) {
				delete_user_meta( $user->ID, self::DEFAULT_PROVIDER_USER_KEY );
			}
		}
	}

	/**
	 * Verifies the attempt for two-factor authentication.
	 *
	 * @param  int    $user_id  The ID of the user.
	 * @param  string $slug  The slug of the two-factor authentication method.
	 *
	 * @return string The lockout message, if the attempt is invalid.
	 * @since 3.3.0
	 */
	public function verify_attempt( int $user_id, string $slug ): string {
		$lockout_message = '';
		$login_settings  = wd_di()->get( Login_Lockout::class );

		$firewall_component = wd_di()->get( Firewall::class );

		$skip_priority_lockout_checks = true;
		foreach ( $this->get_user_ip() as $ip ) {
			if ( ! $firewall_component->skip_priority_lockout_checks( $ip ) ) {
				$skip_priority_lockout_checks = false;
				break;
			}
		}

		/**
		 * Required rules:
		 * hook returns true (since v3.3.0),
		 * if Login Protection is enabled,
		 * the current IP is not in priority points: no allowlisted, no staff and the country is not from allowlisted.
		 */
		if (
			apply_filters( 'wd_2fa_enable_attempts', true )
			&& $login_settings->enabled
			&& ! $skip_priority_lockout_checks
		) {
			$line = get_user_meta( $user_id, 'wd_2fa_attempt_' . $slug, true );
			// Fresh start or there's a record.
			if ( empty( $line ) ) {
				$count      = $this->get_attempt_limit();
				$start_time = time();
			} else {
				[ $count, $start_time ] = explode( '::', $line );
			}
			$end_time   = time();
			$time_limit = $this->get_time_limit();
			// If the time difference between attempts is greater than the limit, clear the attempt counter.
			if ( $end_time - $start_time >= $time_limit ) {
				delete_user_meta( $user_id, 'wd_2fa_attempt_' . $slug );
				$count      = $this->get_attempt_limit();
				$start_time = $end_time;
			}

			$count = (int) $count - 1;
			if ( 1 > $count ) {
				$message = sprintf(
					/* translators: %s: 2FA method slug. */
					esc_html__( 'Lockout occurred: Too many failed 2fa attempts for %s method.', 'wpdef' ),
					$slug
				);
				// since 3.3.0.
				do_action( 'wd_2fa_lockout', $user_id, $message, $time_limit );

				delete_user_meta( $user_id, 'wd_2fa_attempt_' . $slug );

				wp_safe_redirect( home_url() );
				exit;
			}
			// Save number of attempts.
			update_user_meta( $user_id, 'wd_2fa_attempt_' . $slug, $count . '::' . $start_time );
			$lockout_message  = esc_html__( 'INVALID CODE: ', 'wpdef' );
			$lockout_message .= '<span class="two-factor-attempt">' . esc_html__(
				'The two-factor authentication code you entered is incorrect or has expired. Please try again.',
				'wpdef'
			);
			$lockout_message .= '<br/><br/>';
			$lockout_message .= sprintf(
				/* translators: %d: Count. */
				_n( 'You have %d login attempt remaining.', 'You have %d login attempts remaining.', $count, 'wpdef' ),
				$count
			);
			$lockout_message .= '</span>';
		}

		return $lockout_message;
	}

	/**
	 * Encrypts user data with a new key.
	 *
	 * @param  int    $user_id  The ID of the user.
	 * @param  string $plaintext  The plaintext data to be encrypted.
	 * @param  string $state  The state of the data before encryption.
	 *
	 * @return bool True if the data was successfully encrypted, false otherwise.
	 * @throws SodiumException On failure.
	 */
	protected function reencrypt_data( $user_id, $plaintext, $state = 'plaintext' ) {
		$new_key = Crypt::get_encrypted_data( $plaintext );
		if ( ! is_wp_error( $new_key ) ) {
			// If everything is successful, remove an old key.
			delete_user_meta( $user_id, TOTP::TOTP_SECRET_KEY );
			// Update the value with a new key.
			update_user_meta( $user_id, TOTP::TOTP_SODIUM_SECRET_KEY, $new_key );
			// Logging.
			$this->log( 'Update UID: ' . $user_id . '. Previous state: ' . $state, 'internal' );

			return true;
		}
		$this->log( 'Encryption error for UID: ' . $user_id . '. State: ' . $state, 'internal' );

		return false;
	}

	/**
	 * Old states with plaintext or pub key are exist?
	 *
	 * @param  int $user_id  The ID of the user.
	 *
	 * @return bool
	 * @throws SodiumException On failure.
	 */
	public function maybe_update( $user_id ) {
		$old_key = get_user_meta( $user_id, Totp::TOTP_SECRET_KEY, true );
		if ( ! empty( $old_key ) && is_string( $old_key ) ) {
			// Is it a plaintext? It was before v3.3.1.
			if ( TOTP::TOTP_LENGTH === mb_strlen( $old_key, '8bit' ) ) {
				return $this->reencrypt_data( $user_id, $old_key );
			}
			// Is it encrypted via a pub key? It was before v3.4.0.
			$decrypted_data = Legacy_Versions::get_decrypted_data_with_pub_key( $old_key );
			if ( false !== $decrypted_data ) {
				return $this->reencrypt_data( $user_id, $decrypted_data, 'pub-key' );
			}
		}

		return false;
	}

	/**
	 * Finds whether at least anyone user role in array of the enabled 2FA user roles.
	 *
	 * @param  WP_User $user  User instance object.
	 * @param  array   $roles  User roles.
	 *
	 * @return bool Return true for at least one role matches else false return.
	 */
	public function is_auth_enable_for( WP_User $user, array $roles ): bool {
		if ( false === apply_filters( 'wp_defender_2fa_user_enabled', true, $user->ID ) ) {
			return false;
		}

		return ! empty( array_intersect( $this->get_roles( $user ), $roles ) );
	}

	/**
	 * Remove actions when rendering 2FA screen during login.
	 *
	 * @return void
	 * @since 3.5.0
	 */
	public function remove_actions_for_2fa_screen(): void {
		if (
			class_exists( '\WordfenceLS\Controller_WordfenceLS' ) &&
			method_exists( '\WordfenceLS\Controller_WordfenceLS', 'shared' ) &&
			is_object( Controller_WordfenceLS::shared() ) &&
			method_exists( Controller_WordfenceLS::shared(), '_login_enqueue_scripts' )
		) {
			remove_action(
				'login_enqueue_scripts',
				array(
					Controller_WordfenceLS::shared(),
					'_login_enqueue_scripts',
				)
			);
		}
	}

	/**
	 * Queue hooks when this class init.
	 *
	 * @return void
	 */
	public function add_hooks(): void {
		// This will be only displayed on a single site and the main site of MU.
		if ( is_multisite() ) {
			add_filter( 'wpmu_users_columns', array( $this, 'alter_users_table' ) );
			add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'ms_user_row_actions', array( $this, 'display_user_actions' ), 10, 2 );
		} else {
			add_filter( 'manage_users_columns', array( $this, 'alter_users_table' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'user_row_actions', array( $this, 'display_user_actions' ), 10, 2 );
		}
		add_filter( 'manage_users_custom_column', array( $this, 'alter_user_table_row' ), 10, 3 );
		add_filter( 'ms_shortcode_ajax_login', array( $this, 'm2_no_ajax' ) );
	}

	/**
	 * Add a column in the users table, column will be last.
	 *
	 * @param  array $columns  an array of columns.
	 *
	 * @return array
	 */
	public function alter_users_table( array $columns ) {
		$columns = array_slice( $columns, 0, count( $columns ) - 1 )
			+ array( 'defender-two-fa' => esc_html__( 'Two Factor', 'wpdef' ) )
			+ array_slice( $columns, count( $columns ) - 1 );

		return $columns;
	}

	/**
	 * Reset 2FA methods for specific user and display notice.
	 *
	 * @return void
	 */
	public function admin_notices() {
		$screen    = get_current_screen();
		$screen_id = is_multisite() ? 'users-network' : 'users';

		if ( $screen_id !== $screen->id || ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$action  = defender_get_data_from_request( 'action', 'g' );
		$user_id = defender_get_data_from_request( 'user', 'g' );
		if ( ! isset( $action, $user_id ) ) {
			return;
		}
		if ( 'disable_2fa_methods' !== $action ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return;
		}
		// Maybe the value has already been cleared.
		$default_provider = get_user_meta( $user_id, self::DEFAULT_PROVIDER_USER_KEY, true );
		if ( empty( $default_provider ) ) {
			return;
		}
		// Default and enabled 2fa providers are cleared for user.
		update_user_meta( $user_id, self::DEFAULT_PROVIDER_USER_KEY, '' );
		update_user_meta( $user_id, self::ENABLED_PROVIDERS_USER_KEY, '' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: User name. */
					esc_html__( 'Two factor authentication has been reset for %s.', 'wpdef' ),
					'<b>' . esc_html( $user->display_name ) . '</b>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add the link to reset 2FA settings for specific user. It's without bulk actions.
	 *
	 * @param  string[] $actions  The array of user actions.
	 * @param  WP_User  $user  WP_User object for the currently listed user.
	 *
	 * @return array
	 */
	public function display_user_actions( $actions, WP_User $user ) {
		// Only for users that have one enabled 2fa method at least.
		if ( empty( get_user_meta( $user->ID, self::DEFAULT_PROVIDER_USER_KEY, true ) ) ) {
			return $actions;
		}

		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		if ( current_user_can( $cap ) ) {
			$actions['disable_wpdef_2fa_methods'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url( "users.php?action=disable_2fa_methods&amp;user={$user->ID}", 'bulk-users' ),
				esc_html__( 'Reset two factor', 'wpdef' )
			);
		}

		return $actions;
	}

	/**
	 * This method is part of the Two_Fa class and is used to alter the user table row value for the specified column.
	 *
	 * @param  mixed  $val  The current value of the user table row.
	 * @param  string $column_name  The name of the column.
	 * @param  int    $user_id  The ID of the user.
	 *
	 * @return string The altered value of the user table row.
	 */
	public function alter_user_table_row( $val, string $column_name, int $user_id ) {
		// @since 3.3.0. Fix an error from other plugins.
		$val = (string) $val;
		if ( 'defender-two-fa' !== $column_name ) {
			return $val;
		}
		$provider_slug = get_user_meta( $user_id, self::DEFAULT_PROVIDER_USER_KEY, true );
		$provider      = $this->get_provider_by_slug( $provider_slug );
		if ( is_wp_error( $provider ) ) {
			return '';
		}

		return $provider->get_user_label();
	}

	/**
	 * Stop ajax login on membership 2.
	 *
	 * @return bool
	 */
	public function m2_no_ajax() {
		return false;
	}
}