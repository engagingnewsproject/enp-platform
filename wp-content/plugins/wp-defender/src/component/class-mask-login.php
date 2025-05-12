<?php
/**
 * Handles the logic for masking the login URL to enhance security by obscuring the default login paths.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Recovery_Mode_Link_Service;
use WP_Defender\Helper\Request;

/**
 * Handles the logic for masking the login URL to enhance security by obscuring the default login paths.
 */
class Mask_Login extends Component {

	/**
	 * Check if the current user is land on login page, then we can start the block.
	 *
	 * @param  string $requested_path  The path requested by the user.
	 *
	 * @return bool
	 */
	public function is_on_login_page( $requested_path ): bool {
		// Decoded url path, e.g. for case 'wp-%61dmin'.
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
			if ( false !== stristr( $requested_path, "$slug" ) ) {
				return true;
			}
		}
		$login_slugs = apply_filters(
			'wd_login_slugs',
			array(
				'login',
				'dashboard',
				'admin',
				// Because WP redirects from 'login/' to the login page.
				'login/',
			)
		);

		// Check the request path contains default login text.
		if ( in_array( $requested_path, $login_slugs, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the request is land on masked URL.
	 *
	 * @param  string $masked_url  The custom masked URL set by the user.
	 *
	 * @return bool
	 */
	public function is_land_on_masked_url( $masked_url ): bool {
		return ltrim( rtrim( $this->get_request_path(), '/' ), '/' ) === ltrim( rtrim( $masked_url, '/' ), '/' );
	}

	/**
	 * Get the current request URI.
	 *
	 * @param  null|string $site_url  This only need in unit test.
	 *
	 * @return mixed
	 */
	public function get_request_path( $site_url = null ) {
		if ( null === $site_url ) {
			$site_url = $this->get_site_url();
		}

		$request_uri = Request::get_request_uri();

		// If parsed URL is null. PHP v8.1 displays it as deprecated.
		$path = empty( wp_parse_url( $site_url, PHP_URL_PATH ) )
			? ''
			: wp_parse_url( $site_url, PHP_URL_PATH );

		if ( strlen( $path ) && 0 === strpos( $request_uri, $path ) ) {
			$request_uri = substr( $request_uri, strlen( $path ) );
		}
		$request_uri = '/' . ltrim( $request_uri, '/' );

		return wp_parse_url( $request_uri, PHP_URL_PATH );
	}

	/**
	 * Copy cat from the function get_site_url without the filter.
	 *
	 * @param  int|null    $blog_id  Optional. The blog ID in a multisite environment. Default is null.
	 * @param  string      $path  Optional. Additional path to append to the site URL. Default is an empty string.
	 * @param  string|null $scheme  Optional. The scheme to use. Default is null.
	 *
	 * @return string The unfiltered site URL.
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
	 * Redeems a ticket that allows bypassing the masked login.
	 *
	 * @param  string $ticket  The ticket to redeem.
	 *
	 * @return bool Returns true if the ticket is successfully redeemed, false if the ticket is invalid or expired.
	 */
	public function redeem_ticket( $ticket ): bool {
		$settings = wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class );
		$detail   = $settings->express_tickets[ $ticket ] ?? false;
		if ( false === $detail ) {
			return false;
		}

		// Ticket expired.
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
	 * Check if locale should be set.
	 *
	 * @param  string $mask_url  The Mask URL slug.
	 *
	 * @return bool
	 * @since 3.12.0
	 */
	public function is_set_locale( string $mask_url ): bool {
		return $this->is_land_on_masked_url( $mask_url ) && ! empty( defender_get_data_from_request( 'wp_lang', 'g' ) );
	}

	/**
	 * Set locale on Mask Login page.
	 *
	 * @return void
	 * @since 3.12.0
	 */
	public function set_locale(): void {
		$wp_lang = defender_get_data_from_request( 'wp_lang', 'g' );

		if ( ! empty( $wp_lang ) ) {
			switch_to_locale( $wp_lang );
		}
	}

	/**
	 * Check if this is a login page.
	 *
	 * @return bool
	 * @since 4.1.0
	 */
	public function is_login_url(): bool {
		$is_login_path  = wp_parse_url( wp_login_url(), PHP_URL_PATH );
		$requested_path = $this->get_request_path();

		return trim( $requested_path, '/' ) === trim( $is_login_path, '/' );
	}

	/**
	 * Provides the login URL, which might be masked if the feature is active.
	 *
	 * @return string The login URL, potentially masked.
	 */
	public static function maybe_masked_login_url(): string {
		$settings = wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class );
		if ( $settings->is_active() ) {
			return $settings->get_new_login_url();
		} else {
			return wp_login_url();
		}
	}

	/**
	 * Is user hit the recovery link?
	 *
	 * @return bool
	 */
	public function is_recovery_mode(): bool {
		return WP_Recovery_Mode_Link_Service::LOGIN_ACTION_ENTER === defender_get_data_from_request( 'action', 'r' );
	}
}