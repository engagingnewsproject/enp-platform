<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Extra\Base2n;
use Calotes\Helper\Array_Cache;
use WP_Defender\Component\Two_Factor\Providers\Totp;
use WP_Defender\Component\Two_Factor\Providers\Backup_Codes;
use WP_Defender\Component\Two_Factor\Providers\Fallback_Email;

class Two_Fa extends Component {
	/**
	 * The user meta key for the default provider.
	 *
	 * @type string
	 */
	const DEFAULT_PROVIDER_USER_KEY = 'wd_2fa_default_provider';

	/**
	 * The user meta key for enabled providers.
	 *
	 * @type string
	 */
	const ENABLED_PROVIDERS_USER_KEY = 'wd_2fa_enabled_providers';

	/**
	 * @type int
	 */
	const TOTP_DIGIT_COUNT = 6;

	/**
	 * @type int
	 */
	const TOTP_TIME_STEP_SEC = 30;

	/**
	 * @type int
	 */
	const TOTP_LENGTH = 16;

	/**
	 * RFC 4648 base32 alphabet.
	 * @type string
	 */
	const TOTP_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * @type string
	 */
	const DEFAULT_CRYPTO = 'sha1';

	/**
	 * @param int $user_id
	 */
	public function enable_otp( $user_id ) {
		update_user_meta( $user_id, 'defenderAuthOn', 1 );
		update_user_meta( $user_id, 'defenderForceAuth', 0 );
	}

	/**
	 * Gradually move on to using the method is_enabled_otp_for_user(). Used only by the Forminator plugin.
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	public function is_user_enabled_otp( $user_id ) {
		return get_user_meta( $user_id, 'defenderAuthOn', true );
	}

	/**
	 * @param $user_id
	 * @param $roles
	 *
	 * @return bool
	 */
	public function is_force_auth_enable_for( $user_id, $roles ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return false;
		}

		$check = array_intersect( $user->roles, $roles );

		return count( $check ) > 0;
	}

	/**
	 * Verify the OTP of beyond & after TOTP_TIME_STEP_SEC seconds windows. Forminator uses it.
	 *
	 * @param string        $user_code
	 * @param null|\WP_User $user
	 *
	 * @return bool
	 */
	public function verify_otp( $user_code, $user = null ) {
		if ( strlen( $user_code ) < self::TOTP_DIGIT_COUNT ) {
			return false;
		}
		for ( $i = - 30; $i <= self::TOTP_TIME_STEP_SEC; $i ++ ) {
			$counter = 0 === $i ? null : $i * self::TOTP_TIME_STEP_SEC + time();
			$code    = self::generate_otp( $counter, $user );
			if ( hash_equals( $user_code, $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate an OTP code base on current time.
	 *
	 * @param null          $counter
	 * @param null|\WP_User $user
	 *
	 * @return int|string
	 */
	public static function generate_otp( $counter = null, $user = null ) {
		include_once defender_path( 'src/extra/binary-to-text-php/Base2n.php' );
		$base32 = new Base2n( 5, self::TOTP_CHARACTERS, false, true, true );
		$secret = $base32->decode( self::get_user_secret( $user ) );
		if ( is_null( $counter ) ) {
			$counter = time();
		}
		$input = floor( $counter / self::TOTP_TIME_STEP_SEC );
		// According to https://tools.ietf.org/html/rfc4226#section-5.3, should be a 8 bytes value.
		$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $input );
		$hmac = hash_hmac( self::DEFAULT_CRYPTO, $time, $secret, true );
		// Now we have 20 bytes of DEFAULT_CRYPTO, need to short it down. Getting last byte of the hmac.
		$offset     = ord( substr( $hmac, - 1 ) ) & 0x0F;
		$four_bytes = substr( $hmac, $offset, 4 );
		// Now convert it into INT.
		$value = unpack( 'N', $four_bytes );
		$value = $value[1];
		// Make sure it always actual like 32 bits.
		$value = $value & 0x7FFFFFFF;
		// Close.
		$code = $value % pow( 10, self::TOTP_DIGIT_COUNT );
		// In some case we have the 0 before, so it becomes lesser than TOTP_DIGIT_COUNT, make sure it always right.
		return str_pad( $code, self::TOTP_DIGIT_COUNT, '0', STR_PAD_LEFT );
	}

	/**
	 * @param $user
	 *
	 * @return mixed|string
	 */
	public static function get_user_secret( $user = null ) {
		// This should only use in testing.
		if ( is_object( $user ) ) {
			$user_id = $user->ID;
		} else {
			$user_id = get_current_user_id();
		}
		$secret = get_user_meta( $user_id, 'defenderAuthSecret', true );
		if ( ! empty( $secret ) ) {
			return $secret;
		}
		$secret = self::generate_random_strings();
		update_user_meta( $user_id, 'defenderAuthSecret', $secret );

		return $secret;
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	protected static function generate_random_strings( $length = self::TOTP_LENGTH ) {
		if ( defined( 'DEFENDER_2FA_SECRET' ) ) {
			// Only use in test.
			return constant( 'DEFENDER_2FA_SECRET' );
		}
		$strings = self::TOTP_CHARACTERS;
		$secret  = array();
		for ( $i = 0; $i < $length; $i ++ ) {
			$secret[] = $strings[ rand( 0, strlen( $strings ) - 1 ) ];
		}

		return implode( '', $secret );
	}

	/**
	 * Count the total of users, who enables any 2FA method.
	 *
	 * @return int
	 */
	public function count_users_with_enabled_2fa() {
		$slugs = array_keys( $this->get_providers() );
		$query = new \WP_User_Query(
			array(
				// Look over the network.
				'blog_id'      => 0,
				'meta_key'     => self::DEFAULT_PROVIDER_USER_KEY,
				'meta_value'   => $slugs,
				'meta_compare' => 'IN',
			)
		);

		return $query->get_total();
	}

	/**
	 * @return bool
	 */
	public function is_jetpack_sso() {
		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		if ( is_plugin_active_for_network( 'jetpack/jetpack.php' ) ) {
			// Loop through all sites.
			$is_conflict = $settings->is_conflict( 'jetpack/jetpack.php' );
			if ( 0 === $is_conflict ) {
				// No data, init.
				global $wpdb;
				$sql   = "SELECT blog_id FROM `{$wpdb->base_prefix}blogs`";
				$blogs = $wpdb->get_col( $sql );
				foreach ( $blogs as $id ) {
					$options = get_blog_option( $id, 'jetpack_active_modules', array() );
					if ( array_search( 'sso', $options ) ) {
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
				if ( array_search( 'sso', $options ) ) {
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
	 * @return bool
	 */
	public function is_tml() {
		if (
			is_plugin_active( 'theme-my-login/theme-my-login.php' )
			|| is_plugin_active_for_network( 'theme-my-login/theme-my-login.php' )
		) {
			$settings = new \WP_Defender\Model\Setting\Two_Fa();
			$settings->mark_as_conflict( 'theme-my-login/theme-my-login.php' );

			return true;
		}

		return false;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function get_custom_graphic_url( $url = '' ) {
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
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function is_enable_for_current_role( $user ) {
		if ( 0 === count( $user->roles ) ) {
			return true;
		}

		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		if ( ! is_multisite() ) {
			$allowed_for_this_role = array_intersect( $settings->user_roles, $user->roles );
			if ( ! is_array( $allowed_for_this_role ) ) {
				$allowed_for_this_role = [];
			}

			return count( $allowed_for_this_role ) > 0;
		} else {
			$blogs     = get_blogs_of_user( $user->ID );
			$user_roles = array();
			foreach ( $blogs as $blog ) {
				// Get user roles for this blog.
				$u         = new \WP_User( $user->ID, '', $blog->userblog_id );
				$user_roles = array_merge( $u->roles, $user_roles );
			}
			$allowed_for_this_role = array_intersect( $settings->user_roles, $user_roles );

			return count( $allowed_for_this_role ) > 0;
		}
	}

	/**
	 * @param $user_value
	 *
	 * @return bool
	 */
	public function is_enabled_otp_for_user( $user_value ) {
		if ( is_numeric( $user_value ) ) {
			$user = get_user_by( 'id', $user_value );
		} elseif ( $user_value instanceof \WP_User ) {
			$user = $user_value;
		} else{
			return false;
		}

		if ( ! $this->is_enable_for_current_role( $user ) ) {
			return false;
		}

		return (bool) get_user_meta( $user->ID, 'defenderAuthOn', true );
	}

	/**
	 * @return bool
	 */
	public function is_intersected_arrays( $current_user_roles, $plugin_user_roles ) {
		return ! empty( array_intersect( $current_user_roles, $plugin_user_roles ) );
	}

	/**
	 * @since 2.8.0
	 * @return array
	 */
	public function get_providers() {
		$providers = Array_Cache::get( 'providers', 'providers' );
		if ( ! is_array( $providers ) ) {
			$classes = array(
				Totp::class,
				Backup_Codes::class,
				Fallback_Email::class,
			);
			/**
			 * Filter the supplied providers.
			 *
			 * @param array $classes
			 * @since 2.8.0
			 */
			$classes = apply_filters( 'wd_2fa_providers', $classes );
			foreach ( $classes as $class ) {
				$providers[ $class::$slug ] = new $class();
			}
			Array_Cache::set( 'providers', $providers, 'providers' );
		}

		return $providers;
	}

	/**
	 * Get all 2FA Auth providers that are enabled for the specified|current user.
	 *
	 * @param WP_User|null $user
	 *
	 * @return array
	 */
	public function get_enabled_providers_for_user( $user = null ) {
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
		 * @param array  $enabled_providers The enabled providers.
		 * @param int    $user_id           The user ID.
		 * @since 2.8.0
		 */
		return apply_filters( 'wd_2fa_enabled_providers_for_user', $enabled_providers, $user->ID );
	}

	/**
	 * @return array
	*/
	public function get_available_providers_for_user( $user = null ) {
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
	 * @param int $user_id Optional. User ID. Default is 'null'.
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
		 * @param string $provider_slug The provider slug currently being used.
		 * @param int    $user_id       The user ID.
		 */
		return apply_filters( 'wd_2fa_default_provider_for_user', $provider_slug, $user_id );
	}

	/**
	 * @param \WP_User $user
	 * @param string   $slug
	 *
	 * @retun bool
	*/
	public function is_checked_enabled_provider_by_slug( $user, $slug ) {
		$enabled_providers = $this->get_enabled_providers_for_user( $user );

		return in_array( $slug, $enabled_providers, true );
	}

	/**
	 * Send emergency email to users.
	 *
	 * @param string $login_token This will be generated randomly on frontend each time user refresh, an internal OTP.
	 *
	 * @return boolean|\WP_Error
	 */
	public function send_otp_to_email( $login_token ) {
		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		$query    = new \WP_User_Query( [
			'blog_id'    => 0,
			'meta_key'   => 'defender_two_fa_token',
			'meta_value' => $login_token
		] );
		if ( 0 === $query->get_total() ) {
			return new \WP_Error( Error_Code::INVALID, __( 'Your token is invalid.', 'wpdef' ) );
		}

		$user = $query->get_results()[0];
		$code = wp_generate_password( 20, false );
		update_user_meta( $user->ID, 'defenderBackupCode', array(
			'code' => $code,
			'time' => time(),
		) );
		$params = array(
			'display_name' => $user->display_name,
			'passcode'     => $code,
		);
		$two_fa = wd_di()->get( \WP_Defender\Controller\Two_Factor::class );
		$body   = $two_fa->render_partial( 'email/2fa-lost-phone', [
			'body' => nl2br( $settings->email_body ),
		], false );

		foreach ( $params as $key => $val ) {
			$body = str_replace( '{{' . $key . '}}', $val, $body );
		}
		// Main email template.
		$body = $two_fa->render_partial(
			'email/index',
			array(
				'title'        => __( 'Two-Factor Authentication', 'wpdef' ),
				'content_body' => $body,
			),
			false
		);
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
		$from_email = get_bloginfo( 'admin_email' );
		$headers[]  = sprintf( 'From: %s <%s>', $settings->email_sender, $from_email );

		return wp_mail( Fallback_Email::get_backup_email( $user->ID ), $settings->email_subject, $body, $headers );
	}

	/**
	 * @return object|\WP_Error
	 */
	public function get_provider_by_slug( $slug ) {
		foreach ( $this->get_providers() as $key => $provider ) {
			if ( $slug === $key ) {
				return $provider;
			}
		}

		return new \WP_Error( 'opt_fail', __( 'ERROR: Cheatin&#8217; uh?', 'wpdef' ) );
	}
}
