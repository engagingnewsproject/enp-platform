<?php
/**
 * This file contains all functions used in the plugin.
 *
 * @package WP_Defender
 */

use WP_Defender\Central;
use WP_Defender\Component\Crypt;
use WP_Defender\Component\Two_Fa;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\User_Agent;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Model\Setting\Main_Setting;
use WP_Defender\Helper\Request;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Generates the URL for a given asset path in the Defender plugin.
 *
 * @param string $path  The path of the asset.
 * @param bool   $print_url Whether to print the URL or return it.
 *
 * @return string|void The URL of the asset.
 */
function defender_asset_url( string $path, bool $print_url = false ) {
	$url = untrailingslashit( WP_DEFENDER_BASE_URL ) . $path;

	if ( ! $print_url ) {
		return $url;
	}

	echo esc_url_raw( $url );
}

/**
 * Generates the absolute path for a given path relative to the Defender plugin directory.
 *
 * @param  string $path  The relative path within the Defender plugin directory.
 *
 * @return string The absolute path.
 */
function defender_path( string $path ): string {
	$base_path = plugin_dir_path( __DIR__ );

	return $base_path . $path;
}

/**
 * Sanitize submitted data.
 *
 * @param  array $data  The data to sanitize.
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
function defender_wp_config_path(): string {
	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		return ABSPATH . 'wp-config.php';
	}

	if (
		file_exists( dirname( ABSPATH ) . '/wp-config.php' )
		&& ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' )
	) {
		return dirname( ABSPATH ) . '/wp-config.php';
	}

	return ( defined( 'WD_TEST' ) && WD_TEST ) ? '/tmp/wordpress-tests-lib/wp-tests-config.php' : '';
}

/**
 * Check whether we're on Windows platform or not.
 *
 * @return bool
 */
function defender_is_windows(): bool {
	return '\\' === DIRECTORY_SEPARATOR;
}

/**
 * Returns the global DI container for the WP Defender plugin.
 *
 * @return \WPMU_DEV\Defender\Vendor\DI\Container  The global DI container.
 */
function wd_di() {
	global $wp_defender_di;

	return $wp_defender_di;
}

/**
 * Returns the global Central object for the WP Defender plugin.
 *
 * @return Central
 */
function wd_central() {
	global $wp_defender_central;

	return $wp_defender_central;
}

/**
 * Get base action.
 *
 * @return string
 * @since 2.8.0
 */
function defender_base_action(): string {
	return 'wp_defender/v1/hub/';
}

/**
 * Get backward compatibility. Forminator uses this method.
 *
 * @return array
 */
function defender_backward_compatibility() {
	$wpmu_dev        = new WPMUDEV();
	$two_fa_settings = new \WP_Defender\Model\Setting\Two_Fa();
	$controller      = wd_di()->get( Two_Factor::class );
	$collection      = $controller->dump_routes_and_nonces();
	$lost_url        = add_query_arg(
		array(
			'action'     => defender_base_action(),
			'_def_nonce' => $collection['nonces']['send_backup_code'],
			// Add a dummy values to avoid displaying errors, e.g. for the case with null.
			'route'      => $controller->check_route( $collection['routes']['send_backup_code'] ?? 'test' ),
		),
		admin_url( 'admin-ajax.php' )
	);

	return array(
		'is_free'          => ! $wpmu_dev->is_pro(),
		'plugin_url'       => defender_asset_url( '' ),
		'two_fa_settings'  => $two_fa_settings,
		'two_fa_component' => Two_Fa::class,
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
	 * Uses the `timezone_string` option to get a proper timezone if available, otherwise falls back to an offset.
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 * @since 5.3.0
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( '' !== $timezone_string ) {
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
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @return DateTimeZone Timezone object.
	 * @since 5.3.0
	 */
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}

/**
 * Get hostname.
 *
 * @return string
 */
function defender_get_hostname(): string {
	$host = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$host = str_replace( 'www.', '', $host );
	$host = explode( '.', $host );
	$host = array_shift( $host );

	return $host;
}

if ( ! function_exists( 'sanitize_mask_url' ) ) {
	/**
	 * Sanitizes the mask login URL allowing uppercase letters,
	 * Replacing whitespace and a few other characters with dashes and
	 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
	 * Whitespace becomes a dash.
	 *
	 * @param  string $title  The title to be sanitized.
	 *
	 * @return string The sanitized title.
	 */
	function sanitize_mask_url( $title ) {
		$title = wp_strip_all_tags( $title );
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

		return preg_replace( '|-+|', '-', $title );
	}
}

/**
 * Return the noreply email.
 * A utility function which will return common noreply from address.
 *
 * @param  string $filter_tag  Tag name of the filter to override email address.
 *
 * @return string Noreply email.
 */
function defender_noreply_email( string $filter_tag = '' ) {
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
 * Get data of the whitelabel feature from WPMUDEV Dashboard:
 * hide_branding, hide_doc_link, footer_text, hero_image, change_footer.
 *
 * @return array
 * @since 2.5.5
 */
function defender_white_label_status() {
	/* translators: %s: heart icon */
	$footer_text  = sprintf( esc_html__( 'Made with %s by WPMU DEV', 'wpdef' ), '<i class="sui-icon-heart"></i>' );
	$custom_image = (string) apply_filters( 'wpmudev_branding_hero_image', '' );
	$custom_image = trim( $custom_image );
	$whitelabled  = (bool) apply_filters( 'wpmudev_branding_hide_branding', false );

	return array(
		'hide_branding' => apply_filters( 'wpmudev_branding_hide_branding', false ),
		'hide_doc_link' => apply_filters( 'wpmudev_branding_hide_doc_link', false ),
		'footer_text'   => apply_filters( 'wpmudev_branding_footer_text', $footer_text ),
		'hero_image'    => $custom_image,
		'change_footer' => apply_filters( 'wpmudev_branding_change_footer', false ),
		'is_unbranded'  => '' === $custom_image && $whitelabled,
		'is_rebranded'  => '' !== $custom_image && $whitelabled,
	);
}

/**
 * Indicate this is not fresh setup.
 *
 * @since 2.5.5
 */
function defender_no_fresh_install() {
	if ( ! get_site_option( 'wd_nofresh_install' ) ) {
		update_site_option( 'wd_nofresh_install', true );
	}
}

/**
 * Polyfill for PHP version < 7.3.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	/**
	 * Returns the first key of an array.
	 *
	 * @param  array $arr  The input array.
	 *
	 * @return mixed|null The first key of the array, or null if the array is empty.
	 */
	function array_key_first( array $arr ) {
		$arr_keys = array_keys( $arr );

		return $arr_keys[0] ?? null;
	}
}

/**
 * Fetch request url.
 *
 * @return string
 */
function defender_get_request_url(): string {
	return home_url( esc_url( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );
}

/**
 * What is the current WP page?
 *
 * @return string
 */
function defender_get_current_page(): string {
	return defender_get_data_from_request( 'page', 'g' );
}

/**
 * Check that current page is from Defender.
 *
 * @return bool
 */
function is_defender_page(): bool {
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
		'wdf-expert-services',
	);

	return in_array( defender_get_current_page(), $pages, true );
}

/**
 * Return the high contrast css class if it is.
 *
 * @return bool
 * @since 2.7.0
 */
function defender_high_contrast() {
	$model = new Main_Setting();

	return $model->high_contrast_mode;
}

/**
 * Add more cron schedules for plugin modules. E.g. schedules:
 * cleaning completed Scan logs,
 * cleaning temporary firewall IPs,
 * send reports,
 * update MaxMind DB.
 *
 * @param  array $schedules  The schedules.
 *
 * @return array
 * @since 2.7.1
 */
function defender_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['thirty_minutes'] ) ) {
		$schedules['thirty_minutes'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => esc_html__( 'Every Half Hour', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => esc_html__( 'Weekly', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['monthly'] ) ) {
		$schedules['monthly'] = array(
			'interval' => MONTH_IN_SECONDS,
			'display'  => esc_html__( 'Once Monthly', 'wpdef' ),
		);
	}
	// Todo: find the right solution because 'monthly' (from Firewall)='thirty_days' (from Security_Key tweak).
	// For regeneration of security keys/salts. Schedules: 30, 60, 90 days, 6 months and 1 year.
	if ( ! isset( $schedules['thirty_days'] ) ) {
		$schedules['thirty_days'] = array(
			'interval' => 2592000,
			'display'  => esc_html__( '30 days', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['sixty_days'] ) ) {
		$schedules['sixty_days'] = array(
			'interval' => 5184000,
			'display'  => esc_html__( '60 days', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['ninety_days'] ) ) {
		$schedules['ninety_days'] = array(
			'interval' => 7776000,
			'display'  => esc_html__( '90 days', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['six_months'] ) ) {
		$schedules['six_months'] = array(
			'interval' => 15780000,
			'display'  => esc_html__( '6 months', 'wpdef' ),
		);
	}
	if ( ! isset( $schedules['one_year'] ) ) {
		$schedules['one_year'] = array(
			'interval' => 31536000,
			'display'  => esc_html__( '1 year', 'wpdef' ),
		);
	}

	return $schedules;
}

/**
 * Generate random string.
 *
 * @param  int    $length  Length of random string.
 * @param  string $strings  Characters to include in a random string.
 *
 * @return string
 * @since 3.0.0
 */
function defender_generate_random_string( int $length = 16, string $strings = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567' ) {
	if ( defined( 'DEFENDER_2FA_SECRET' ) ) {
		// Only use in test.
		return constant( 'DEFENDER_2FA_SECRET' );
	}

	$secret = array();
	for ( $i = 0; $i < $length; $i++ ) {
		$secret[] = $strings[ Crypt::random_int( 0, strlen( $strings ) - 1 ) ];
	}

	return implode( '', $secret );
}

/**
 * Either return array or echo json.
 *
 * @param  mixed $data  A Data to be returned or echoed.
 * @param  bool  $success  Is it a success or failure.
 * @param  bool  $is_return  True if data needs to be returned.
 *
 * @return array|void
 * @since 3.0.0
 */
function defender_maybe_echo_json( $data, $success, $is_return ) {
	if ( true === $is_return ) {
		return array(
			'success' => $success,
			'data'    => $data,
		);
	} else {
		$success ? wp_send_json_success( $data ) : wp_send_json_error( $data );
	}
}

/**
 * Get translations for the 'wpdef' text domain.
 *
 * @return array List of words/phrases and their translations.
 * @global Mo[] $l10n An array of all currently loaded text domains.
 */
function defender_gettext_translations(): array {
	global $l10n;

	// Check if the 'wpdef' text domain is loaded.
	if ( ! isset( $l10n['wpdef'] ) ) {
		return array();
	}

	$items = array();

	/**
	 * Go through all the translation entries in the 'wpdef' text domain.
	 *
	 * @var Translation_Entry $value
	 */
	foreach ( $l10n['wpdef']->entries as $value ) {
		// If there's a translation, use it; otherwise, use the original word/phrase.
		$items[ $value->key() ] = isset( $value->translations[0] ) ? $value->translations[0] : $value->key();
	}

	// Return the list of words/phrases and their translations.
	return $items;
}

/**
 * Get string replacement regardless of the operating system.
 *
 * @param  string $path  The path to be replaced.
 *
 * @return string
 * @since 3.3.0
 */
function defender_replace_line( $path ): string {
	return str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
}

/**
 * Generates the support ticket text for the Defender plugin.
 *
 * @return string The support ticket text, including the formatted link.
 */
function defender_support_ticket_text(): string {
	return sprintf(
		/* translators: 1. Support link. */
		esc_html__( 'Still, having trouble? %1$s.', 'wpdef' ),
		'<a target="_blank" href="' . WP_DEFENDER_SUPPORT_LINK . '">' . esc_html__( 'Open a support ticket', 'wpdef' ) . '</a>'
	);
}

/**
 * The message is shown on the inappropriate access of Safe Repair feature.
 *
 * @return string
 */
function defender_quarantine_pro_only(): string {
	return esc_html__( 'Safe Repair feature is only for Pro', 'wpdef' );
}

/**
 * Retrieves the user agent from the $_SERVER super global or returns a default string.
 *
 * @param  string $default_string  The default string to return if the user agent is empty. Default is an empty string.
 *
 * @return string The cleaned user agent or the default string.
 */
function defender_get_user_agent( $default_string = '' ) {
	$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );

	return '' !== $user_agent ? User_Agent::fast_cleaning( $user_agent ) : $default_string;
}

/**
 * Check if it is a CLI request.
 *
 * @return bool
 * @since 4.2.0
 */
function defender_is_wp_cli() {
	return defined( 'WP_CLI' ) && WP_CLI;
}

/**
 * Check if the current request is a REST API request.
 * This function checks if the current request URI contains the REST API prefix,
 * indicating that it's a request to the WordPress REST API.
 *
 * @return bool Whether the current request is a REST API request.
 * @since 4.2.0
 */
function defender_is_rest_api_request(): bool {
	$request_uri = Request::get_request_uri();
	if ( '' === $request_uri ) {
		return false;
	}

	$rest_prefix = trailingslashit( rest_get_url_prefix() );

	return false !== strpos( $request_uri, $rest_prefix );
}

/**
 * Handle deprecated functions by logging or triggering actions.
 * This function is a wrapper for WordPress's _deprecated_function() function.
 * It is used to handle deprecated functions by either logging a deprecation
 * message or triggering an action. It checks if the current request is an AJAX
 * request or a REST API request and acts accordingly.
 *
 * @param  string $function_name  The function that was called.
 * @param  string $version  The version number that deprecated the function.
 * @param  string $replacement  (Optional) The function that should be used instead.
 *
 * @return void
 * @since 4.2.0
 */
function defender_deprecated_function( string $function_name, string $version, string $replacement = '' ): void {
	/**
	 * Filters whether to trigger an error for deprecated functions.
	 *
	 * @param  bool  $trigger  Whether to trigger the error for deprecated functions. Default false.
	 *
	 * @since 4.2.1
	 */
	if ( WP_DEBUG && apply_filters( 'defender_deprecated_function_trigger_error', false ) ) {
		if ( wp_doing_ajax() || defender_is_rest_api_request() ) {
			do_action( 'deprecated_function_run', $function_name, $replacement, $version );

			$log_string  = "Function {$function_name} is deprecated since version {$version}!";
			$log_string .= '' !== $replacement ? " Use {$replacement} instead." : '';
			wp_die( esc_html( $log_string ) );
		} else {
			_deprecated_function( esc_html( $function_name ), esc_html( $version ), esc_html( $replacement ) );
		}
	}
}

if ( ! function_exists( 'defender_get_data_from_request' ) ) {
	/**
	 * Retrieves the value of a specific server data key after sanitizing it.
	 *
	 * @param  string|null $key  The key of the data to retrieve from the request. If empty, the entire $_REQUEST or $_SERVER array will be returned.
	 * @param  string      $source  The source of the data. Default is 'r' for $_REQUEST. Other options are 's' for $_SERVER, 'c' for $_COOKIE and 'f' for $_FILES.
	 * @param  string      $nonce_key  The nonce key for verification.
	 * @param  string      $nonce_action  The nonce action for verification.
	 *
	 * @return array|string|null The sanitized value of the server data key.
	 */
	function defender_get_data_from_request(
		?string $key,
		string $source,
		string $nonce_key = '',
		string $nonce_action = ''
	) {
		if ( in_array( $source, array( 'r', 'p', 'g' ), true ) ) {
			if (
				'' !== $nonce_key && ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_key ] ?? '' ) ),
					$nonce_action
				)
			) {
				return null;
			}
		}
		switch ( $source ) {
			case 'r':
				$data = $_REQUEST;
				break;
			case 'p':
				$data = $_POST;
				break;
			case 'g':
				$data = $_GET;
				break;
			case 's':
				$data = $_SERVER;
				break;
			case 'c':
				$data = $_COOKIE;
				break;
			case 'f':
				$data = $_FILES;
				break;
			default:
				$data = array();
				break;
		}

		if ( null === $key || '' === $key ) {
			return $data;
		} elseif ( 's' === $source ) {
			return sanitize_text_field( wp_unslash( $data[ $key ] ?? '' ) );
		} elseif ( 'data' === $key ) {
			return json_decode( sanitize_text_field( wp_unslash( $data[ $key ] ?? '' ) ), true );
		} elseif ( 'f' === $source && 'file' === $key && isset( $data[ $key ] ) ) {
			if ( isset( $data[ $key ]['name'] ) && '' !== $data[ $key ]['name'] ) {
				$data[ $key ]['name'] = sanitize_file_name( $data[ $key ]['name'] );
			}

			return $data[ $key ];
		}

		return sanitize_text_field( $data[ $key ] ?? '' );
	}
}

/**
 * Check if arrays are same.
 *
 * @param  array $array1  The first array to compare.
 * @param  array $array2  The second array to compare.
 *
 * @return bool True if arrays are equal, false otherwise.
 */
function defender_are_arrays_equal( $array1, $array2 ): bool {
	if ( count( $array1 ) !== count( $array2 ) ) {
		return false;
	}

	// Sort both arrays.
	sort( $array1 );
	sort( $array2 );

	return $array1 === $array2;
}

/**
 * Get a feature state on WPMU DEV hosting.
 *
 * @param string $feature_key  The feature key, e.g. xmlrpc_block, globaliplist or waf.
 *
 * @return bool|string True or false if the feature is enabled or disabled, or an empty string if the feature is not found.
 */
function defender_get_hosting_feature_state( string $feature_key ) {
	if ( function_exists( 'wpmudev_hosting_features' ) ) {
		$states = wpmudev_hosting_features();

		return isset( $states[ $feature_key ] ) ? $states[ $feature_key ] : '';
	}

	return '';
}

/**
 * Get an internal log file name.
 *
 * @return string
 */
function wd_internal_log(): string {
	return Central::INTERNAL_LOG;
}

/**
 * Retrieves the current time, allowing overrides for testing.
 *
 * @return int The current timestamp.
 */
function defender_get_current_time() {
	/**
	 * Filter the current time.
	 *
	 * @since 5.2.0
	 *
	 * @param int $time The current timestamp.
	 *
	 * @return int The current/filtered timestamp.
	 */
	return (int) apply_filters( 'wpdef_current_time', time() );
}

/**
 * Get the current site domain.
 *
 * @return string The determined domain name.
 */
function defender_get_domain() {
	static $domain = '';

	if ( '' === $domain ) {
		$domain = is_multisite()
		? ( get_network()->domain ?? '' )
		: wp_parse_url( get_site_url(), PHP_URL_HOST );
	}

	/**
	 * Filter the current site domain.
	 *
	 * @param string $domain The determined domain name.
	 *
	 * @return string The filtered domain name.
	 *
	 * @since 5.2.0
	 */
	$domain = (string) apply_filters( 'wpdef_current_site_domain', $domain );

	return esc_html( $domain );
}

/**
 * WP_DEFENDER_PRO sometimes doesn't match WPMUDEV::is_pro().
 *
 * @return bool
 */
function defender_is_wp_org_version(): bool {
	return ! wd_di()->get( WPMUDEV::class )->is_pro()
		&& ( defined( 'WP_DEFENDER_PRO' ) && ! WP_DEFENDER_PRO );
}

/**
 * Get the user agent of the plugin.
 *
 * @return string
 */
function defender_get_own_user_agent(): string {
	return defender_is_wp_org_version()
		? sprintf( 'Mozilla/5.0 (compatible; Defender/%1$s)', DEFENDER_VERSION )
		: sprintf(
			'Mozilla/5.0 (compatible; WPMU DEV Defender/%1$s; +https://wpmudev.com)',
			DEFENDER_VERSION
		);
}