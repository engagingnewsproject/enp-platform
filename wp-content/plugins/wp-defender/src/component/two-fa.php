<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Extra\Base2n;

class Two_Fa extends Component {

	public function enable_otp( $user_id ) {
		update_user_meta( $user_id, 'defenderAuthOn', 1 );
		update_user_meta( $user_id, 'defenderForceAuth', 0 );
	}

	/**
	 * @param $user_id
	 *
	 * @return int
	 */
	public function is_user_enabled_otp( $user_id ) {
		return apply_filters( 'wp_defender_2fa_user_enabled',
			(int) get_user_meta( $user_id, 'defenderAuthOn', true ), $user_id );
	}

	/**
	 * @param $user_id
	 * @param $roles
	 *
	 * @return int|void
	 */
	public function is_force_auth_enable_for( $user_id, $roles ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return;
		}

		$check = array_intersect( $user->roles, $roles );

		return count( $check );
	}

	/**
	 * Send emergency email to users
	 *
	 * @param  string  $login_token  - this will be generate randomly on frontend
	 * each time user refresh, an internal OTP
	 *
	 * @return boolean|\WP_Error
	 */
	public function send_otp_to_email( $login_token ) {
		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		if ( $settings->lost_phone === false ) {
			return false;
		}
		$query = new \WP_User_Query( [
			'blog_id'    => 0,
			'meta_key'   => 'defender_two_fa_token',
			'meta_value' => $login_token
		] );
		if ( $query->get_total() === 0 ) {
			return new \WP_Error( Error_Code::INVALID, __( 'Your token is invalid', 'wpdef' ) );
		}

		$user = $query->get_results()[0];
		$code = wp_generate_password( 20, false );
		update_user_meta( $user->ID, 'defenderBackupCode', array(
			'code' => $code,
			'time' => time()
		) );
		$params = [
			'display_name' => $user->display_name,
			'passcode'     => $code,
		];
		$body   = nl2br( $settings->email_body );

		foreach ( $params as $key => $val ) {
			$body = str_replace( '{{' . $key . '}}', $val, $body );
		}
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
		$from_email = get_bloginfo( 'admin_email' );
		$headers[]  = sprintf( 'From: %s <%s>', $settings->email_sender, $from_email );
		$ret        = wp_mail( $this->get_backup_email( $user->ID ), $settings->email_subject, $body, $headers );

		return $ret;
	}

	/**
	 * @param $user_id
	 *
	 * @return bool|mixed|string
	 */
	public function get_backup_email( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$email = get_user_meta( $user_id, 'defenderAuthEmail', true );
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
	 * Verify the OTP of beyond & after 30 seconds windows
	 *
	 * @param $user_code
	 * @param $user \WP_User
	 *
	 * @return bool
	 */
	public function verify_otp( $user_code, $user = null ) {
		if ( strlen( $user_code ) < 6 ) {
			return false;
		}
		for ( $i = - 30; $i <= 30; $i ++ ) {
			$counter = 0 === $i ? null : $i * 30 + time();
			$code    = self::generate_otp( $counter, $user );
			if ( hash_equals( $user_code, $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate an OTP code base on current time
	 *
	 * @param  null  $counter
	 * @param $user \WP_User
	 *
	 * @return int|string
	 */
	public static function generate_otp( $counter = null, $user = null ) {
		include_once defender_path( 'src/extra/binary-to-text-php/Base2n.php' );
		$base32 = new Base2n( 5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true );
		$secret = $base32->decode( self::get_user_secret( $user ) );
		if ( is_null( $counter ) ) {
			$counter = time();
		}
		$input = floor( $counter / 30 );
		//according to https://tools.ietf.org/html/rfc4226#section-5.3, should be a 8 bytes value
		$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $input );
		$hmac = hash_hmac( 'sha1', $time, $secret, true );
		//now we have 20 bytes sha1, need to short it down
		//getting last byte of the hmac
		$offset     = ord( substr( $hmac, - 1 ) ) & 0x0F;
		$four_bytes = substr( $hmac, $offset, 4 );
		//now convert it into INT
		$value = unpack( 'N', $four_bytes );
		$value = $value[1];
		//make sure it always act like 32 bits
		$value = $value & 0x7FFFFFFF;
		//we so close
		$code = $value % pow( 10, 6 );
		//in some case we have the 0 before, so it become lesser than 6, make sure it always right
		$code = str_pad( $code, 6, '0', STR_PAD_LEFT );

		return $code;
	}

	/**
	 * Generate a QR code for apps can use
	 *  1. Authy
	 *  2. Google Authenticator
	 *  3. Microsoft Authenticator
	 */
	public static function generate_qr_code() {
		$settings = new \WP_Defender\Model\Setting\Two_Fa();
		$issuer   = $settings->app_title;
		$user     = wp_get_current_user();
		$chl      = ( 'otpauth://totp/' . rawurlencode( $issuer ) . ':' . rawurlencode( $user->user_email ) . '?secret=' . self::get_user_secret() . '&issuer=' . rawurlencode( $issuer ) );
		require_once defender_path( 'src/extra/phpqrcode/phpqrcode.php' );

		\QRcode::svg( $chl, false, QR_ECLEVEL_L, 4 );
	}

	/**
	 * @param  null  $user
	 *
	 * @return mixed|string
	 */
	protected static function get_user_secret( $user = null ) {
		/**
		 * THis should only use in testing
		 */
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
	 * @param  int  $length
	 *
	 * @return string
	 */
	protected static function generate_random_strings( $length = 16 ) {
		if ( defined( 'DEFENDER_2FA_SECRET' ) ) {
			//only use in test
			return constant( 'DEFENDER_2FA_SECRET' );
		}
		$strings = 'ABCDEFGHIJKLMNOPQRSTUVWXYS234567';
		$secret  = array();
		for ( $i = 0; $i < $length; $i ++ ) {
			$secret[] = $strings[ rand( 0, strlen( $strings ) - 1 ) ];
		}

		return implode( '', $secret );
	}

	/**
	 * Count the total of users, who enable 2fa
	 * @return int
	 */
	public function count_2fa_enabled() {
		$query = new \WP_User_Query(
			array(
				// look over the network
				'blog_id'    => 0,
				'meta_key'   => 'defenderAuthOn',
				'meta_value' => true,
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
			//loop through all sites
			$is_conflict = $settings->is_conflict( 'jetpack/jetpack.php' );
			if ( 0 === $is_conflict ) {
				//no data, init
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
				//get the data from cache
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
			//nothing here, surely it will cause broken, fall back to default
			return defender_asset_url( '/assets/img/2factor-disabled.svg' );
		} else {
			//image should be under wp-content/.., so we catch that part
			if ( preg_match( '/(\/wp-content\/.+)/', $url, $matches ) ) {
				$rel_path = $matches[1];
				$rel_path = ltrim( $rel_path, '/' );
				$abs_path = ABSPATH . $rel_path;
				if ( ! file_exists( $abs_path ) ) {
					//fallback
					return defender_asset_url( '/assets/img/2factor-disabled.svg' );
				} else {
					//should replace with our site url
					return get_site_url( null, $rel_path );
				}
			}

			return defender_asset_url( '/assets/img/2factor-disabled.svg' );
		}
	}

	/**
	 * @param WP_User $user
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
				//get user roles for this blog
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
	public function is_user_enable_otp( $user_value ) {
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

		return 1 == get_user_meta( $user->ID, 'defenderAuthOn', true );
	}
}