<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * @param $path
 *
 * @return string
 */
function defender_asset_url( $path ) {
	$base_url = plugin_dir_url( __DIR__ );

	return untrailingslashit( $base_url ) . $path;
}

/**
 * @param $path
 *
 * @return string
 */
function defender_path( $path ) {
	$base_path = plugin_dir_path( __DIR__ );

	return $base_path . $path;
}

/**
 * Sanitize submitted data.
 *
 * @param  array  $data
 *
 * @return array
 */
function defender_sanitize_data( $data ) {
	foreach ( $data as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = defender_sanitize_data( $value );
		} else {
			$value = sanitize_textarea_field( $value );
		}
	}

	return $data;
}

/**
 * Retrieve wp-config.php file path.
 *
 * @return string
 */
function defender_wp_config_path() {
	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		return ABSPATH . 'wp-config.php';
	}

	if (
		@file_exists( dirname( ABSPATH ) . '/wp-config.php' )
		&& ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' )
	) {
		return dirname( ABSPATH ) . '/wp-config.php';
	}

	if ( defined( 'WD_TEST' ) && WD_TEST ) {
		return '/tmp/wordpress-tests-lib/wp-tests-config.php';
	}
}

/**
 * Check whether we're on Windows platform or not.
 *
 * @return bool
 */
function defender_is_windows() {
	return '\\' === DIRECTORY_SEPARATOR;
}

/**
 * @return \DI\Container
 */
function wd_di() {
	global $wp_defender_di;

	return $wp_defender_di;
}

/**
 * @return \WP_Defender\Central
 */
function wd_central() {
	global $wp_defender_central;

	return $wp_defender_central;
}

/**
 * Get backward compatibility.
 *
 * @return array
 */
function defender_backward_compatibility() {
	$wpmu_dev        = new \WP_Defender\Behavior\WPMUDEV();
	$two_fa_settings = new \WP_Defender\Model\Setting\Two_Fa();
	$list            = wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->dump_routes_and_nonces();
	$lost_url        = add_query_arg(
		array(
			'action'     => 'wp_defender/v1/hub/',
			'_def_nonce' => $list['nonces']['send_backup_code'],
			'route'      => $list['routes']['send_backup_code'],
		),
		admin_url( 'admin-ajax.php' )
	);

	return array(
		'is_free'          => ! $wpmu_dev->is_pro(),
		'plugin_url'       => defender_asset_url( '' ),
		'two_fa_settings'  => $two_fa_settings,
		'two_fa_component' => \WP_Defender\Component\Two_Fa::class,
		'lost_url'         => $lost_url,
	);
}

/**
 * Polyfill functions for supporting WordPress 5.3.
 *
 * @since 2.4.2
 */
if ( ! function_exists( 'wp_timezone_string' ) ) {
	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * Uses the `timezone_string` option to get a proper timezone if available,
	 * otherwise falls back to an offset.
	 *
	 * @since 5.3.0
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @since 5.3.0
	 *
	 * @return DateTimeZone Timezone object.
	 */
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}

if ( ! function_exists( 'defender_wp_check_php_version' ) ) {
	/**
	 * Polyfill for function of WP core wp_check_php_version().
	 *
	 * @since WP 5.1.0
	 * @since WP 5.1.1 Added the {@see 'wp_is_php_version_acceptable'} filter.
	 *
	 * @return array|false Array of PHP version data. False on failure.
	 */
	function defender_wp_check_php_version() {
		$version = phpversion();
		$key     = md5( $version );

		$response = get_site_transient( 'php_check_' . $key );
		if ( false === $response ) {
			$url = 'http://api.wordpress.org/core/serve-happy/1.0/';
			if ( wp_http_supports( array( 'ssl' ) ) ) {
				$url = set_url_scheme( $url, 'https' );
			}

			$url = add_query_arg( 'php_version', $version, $url );

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			/**
			 * Response should be an array with:
			 *  'recommended_version' - string - The PHP version recommended by WordPress.
			 *  'is_supported' - boolean - Whether the PHP version is actively supported.
			 *  'is_secure' - boolean - Whether the PHP version receives security updates.
			 *  'is_acceptable' - boolean - Whether the PHP version is still acceptable for WordPress.
			 */
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $response ) ) {
				return false;
			}

			set_site_transient( 'php_check_' . $key, $response, WEEK_IN_SECONDS );
		}

		if ( isset( $response['is_acceptable'] ) && $response['is_acceptable'] ) {
			/**
			 * Filters whether the active PHP version is considered acceptable by WordPress.
			 *
			 * Returning false will trigger a PHP version warning to show up in the admin dashboard to administrators.
			 *
			 * This filter is only run if the wordpress.org Serve Happy API considers the PHP version acceptable, ensuring
			 * that this filter can only make this check stricter, but not loosen it.
			 *
			 * @since 5.1.1
			 *
			 * @param bool   $is_acceptable Whether the PHP version is considered acceptable. Default true.
			 * @param string $version       PHP version checked.
			 */
			$response['is_acceptable'] = (bool) apply_filters( 'wp_is_php_version_acceptable', true, $version );
		}

		return $response;
	}
}

/**
 * Get hostname.
 *
 * @return string|null
 */
function defender_get_hostname() {
	$host = parse_url( get_site_url(), PHP_URL_HOST );
	$host = str_replace( 'www.', '', $host );
	$host = explode( '.', $host );
	if ( is_array( $host ) ) {
		$host = array_shift( $host );
	} else {
		$host = null;
	}

	return $host;
}

if ( ! function_exists( 'sanitize_mask_url' ) ) {
	/**
	 * Sanitizes the mask login URL allowing uppercase letters,
	 * Replacing whitespace and a few other characters with dashes and
	 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
	 * Whitespace becomes a dash.
	 *
	 * @param string $title     The title to be sanitized.
	 * @return string The sanitized title.
	 */
	function sanitize_mask_url( $title ) {
		$title = strip_tags( $title );
		// Preserve escaped octets.
		$title = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title );
		// Remove percent signs that are not part of an octet.
		$title = str_replace( '%', '', $title );
		// Restore octets.
		$title = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title );

		if ( seems_utf8( $title ) ) {
			$title = utf8_uri_encode( $title, 200 );
		}

		// Kill entities.
		$title = preg_replace( '/&.+?;/', '', $title );
		$title = str_replace( '.', '-', $title );

		$title = preg_replace( '/[^%a-zA-Z0-9 _-]/', '', $title );
		$title = preg_replace( '/\s+/', '-', $title );
		$title = preg_replace( '|-+|', '-', $title );

		return $title;
	}
}

/**
 * Return the noreply email.
 *
 * An utility function which will return common noreply from address.
 *
 * @param string $filter_tag Tag name of the filter to override email address.
 *
 * @return string Noreply email.
 */
function defender_noreply_email( $filter_tag = '' ) {
	$host = wp_parse_url( get_site_url(), PHP_URL_HOST );

	if ( 'www.' === substr( $host, 0, 4 ) ) {
		$host = substr( $host, 4 );
	}

	$no_reply_email = 'noreply@' . $host;

	if ( strlen( $filter_tag ) > 0 ) {
		$no_reply_email = apply_filters( $filter_tag, $no_reply_email );
	}

	return $no_reply_email;
}

/**
 * Noreply email header.
 *
 * Generate noreply email header with HTML UTF-8 support.
 *
 * @param string $email Email.
 *
 * @return array Returns the email headers.
 */
function defender_noreply_html_header( $email ) {
	$headers = array(
		'From: Defender <' . $email . '>',
		'Content-Type: text/html; charset=UTF-8',
	);

	return $headers;
}

/**
 * Get data of the whitelabel feature from WPMUDEV Dashboard:
 * hide_branding, hide_doc_link, footer_text, hero_image, change_footer.
 *
 * @since 2.5.5
 * @return array
 */
function defender_white_label_status() {
	/* translators: %s: heart icon */
	$footer_text  = sprintf( __( 'Made with %s by WPMU DEV', 'wpdef' ), '<i class="sui-icon-heart"></i>' );
	$custom_image = apply_filters( 'wpmudev_branding_hero_image', '' );
	$whitelabled  = apply_filters( 'wpmudev_branding_hide_branding', false );

	return array(
		'hide_branding' => apply_filters( 'wpmudev_branding_hide_branding', false ),
		'hide_doc_link' => apply_filters( 'wpmudev_branding_hide_doc_link', false ),
		'footer_text'   => apply_filters( 'wpmudev_branding_footer_text', $footer_text ),
		'hero_image'    => $custom_image,
		'change_footer' => apply_filters( 'wpmudev_branding_change_footer', false ),
		'is_unbranded'  => empty( $custom_image ) && $whitelabled,
		'is_rebranded'  => ! empty( $custom_image ) && $whitelabled,
	);
}

/**
 * Indicate this is not fresh install.
 *
 * @since 2.5.5
 * @return void
 */
function defender_no_fresh_install() {
	if ( empty( get_site_option( 'wd_nofresh_install' ) ) ) {
		update_site_option( 'wd_nofresh_install', true );
	}
}

/**
 * Polyfill for PHP version < 7.3.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	function array_key_first( array $arr ) {
		return array_keys( $arr )[0];
	}
}

/**
 * Fetch request url.
 */
function defender_get_request_url() {
	return home_url( esc_url( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );
}

/**
 * Check that current page is from Defender.
 *
 * @return bool
*/
function defender_current_page() {
	$pages = array(
		'wp-defender',
		'wdf-hardener',
		'wdf-scan',
		'wdf-logging',
		'wdf-ip-lockout',
		'wdf-waf',
		'wdf-2fa',
		'wdf-advanced-tools',
		'wdf-notification',
		'wdf-setting',
		'wdf-tutorial',
	);
	$page  = isset( $_GET['page'] ) ? $_GET['page'] : null;

	return in_array( $page, $pages, true );
}
