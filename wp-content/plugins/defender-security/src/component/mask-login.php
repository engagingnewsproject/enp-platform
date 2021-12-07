<?php

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;

/**
 * Doing the logic for mask login module
 *
 * Class Mask_Login
 *
 * @package WP_Defender\Component
 */
class Mask_Login extends Component {

	/**
	 * Check if the current user is land on login page, then we can start the block
	 * @param string $requested_path
	 *
	 * @return bool
	 */
	public function is_on_login_page( $requested_path ) {
		// decoded url path, e.g. for case 'wp-%61dmin'
		$requested_path = rawurldecode( strtolower( $requested_path ) );
		$login_slugs    = apply_filters(
			'wd_login_strict_slugs',
			array(
				'wp-admin',
				'wp-login',
				'wp-login.php',
			)
		);
		foreach ( $login_slugs as $slug ) {
			if ( stristr( $requested_path, "$slug" ) !== false ) {
				return true;
			}
		}
		$login_slugs = apply_filters(
			'wd_login_slugs',
			array(
				'login',
				'dashboard',
				'admin',
				//because WP redirects from 'login/' to the login page
				'login/',
			)
		);

		// check the request path contains default login text
		if ( in_array( $requested_path, $login_slugs, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the request is land on masked URL
	 *
	 * @param $masked_url string
	 *
	 * @return boolean
	 */
	public function is_land_on_masked_url( $masked_url ) {
		return ltrim( rtrim( $this->get_request_path(), '/' ), '/' ) === ltrim( rtrim( $masked_url, '/' ), '/' );
	}

	/**
	 * Get the current request URI
	 *
	 * @param  null  $site_url  - This only need in unit test
	 *
	 * @return string
	 */
	public function get_request_path( $site_url = null ) {
		if ( null === $site_url ) {
			$site_url = $this->get_site_url();
		}
		$request_uri = $_SERVER['REQUEST_URI'];
		$path        = trim( wp_parse_url( $site_url, PHP_URL_PATH ) );
		if ( strlen( $path ) && 0 === strpos( $request_uri, $path ) ) {
			$request_uri = substr( $request_uri, strlen( $path ) );
		}
		$request_uri = '/' . ltrim( $request_uri, '/' );

		return wp_parse_url( $request_uri, PHP_URL_PATH );
	}

	/**
	 * Copy cat from the function get_site_url without the filter
	 *
	 * @param  null  $blog_id
	 * @param  string  $path
	 * @param  null  $scheme
	 *
	 * @return mixed|void
	 */
	private function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
		if ( empty( $blog_id ) || ! is_multisite() ) {
			$url = get_option( 'siteurl' );
		} else {
			switch_to_blog( $blog_id );
			$url = get_option( 'siteurl' );
			restore_current_blog();
		}

		$url = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return $url;
	}

	/**
	 * Generate a random unique onetime pass and store in user meta
	 * Later we can use it to by pass the mask admin
	 *
	 * @return bool
	 */
	public function generate_ticket() {
		$otp                               = wp_generate_password( 12, false );
		$settings                          = new \WP_Defender\Model\Setting\Mask_Login();
		$settings->express_tickets[ $otp ] = array(
			'otp'     => $otp,
			'bind_to' => null,
			'expiry'  => strtotime( '+3 day' ),
			'used'    => 0,
		);
		$settings->save();

		return $otp;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	public function maybe_append_ticket_to_url( $url ) {
		return add_query_arg( 'ticket', $this->generate_ticket(), $url );
	}

	/**
	 * @param $ticket
	 *
	 * @return bool
	 */
	public function redeem_ticket( $ticket ) {
		$settings = new \WP_Defender\Model\Setting\Mask_Login();
		$detail   = isset( $settings->express_tickets[ $ticket ] ) ? $settings->express_tickets[ $ticket ] : false;
		if ( false === $detail ) {
			return false;
		}

		/**
		 * ticket expired
		 */
		if ( $detail['expiry'] < time() ) {
			unset( $settings->express_tickets[ $ticket ] );
			$settings->save();

			return false;
		}

		$detail['used']                      += 1;
		$settings->express_tickets[ $ticket ] = $detail;
		$settings->save();

		return true;
	}

	/**
	 * Check if the HTTP_USER_AGENT is a bot
	 *
	 * @return bool
	 */
	public function is_bot_request() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		if ( preg_match(
			'/bot|crawl|curl|dataprovider|search|get|spider|find|java|majesticsEO|google|yahoo|teoma|contaxe|yandex|libwww-perl|facebookexternalhit/i',
			$_SERVER['HTTP_USER_AGENT']
		) ) {
			return true;
		} else {
			return false;
		}
	}
}
