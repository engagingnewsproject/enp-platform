<?php
/**
 * Class Utils holds common functions used by the plugin.
 *
 * Class has the following structure:
 * I.   General helper functions
 * II.  Layout functions
 * III. Time and date functions
 * IV.  Link and url functions
 * V.   Modules functions
 *
 * @package Hummingbird\Core
 * @since 1.8
 */

namespace Hummingbird\Core;

use Hummingbird\WP_Hummingbird;
use WP_User;
use WPMUDEV_Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils
 */
class Utils {

	/***************************
	 *
	 * I. General helper functions
	 * is_member()
	 * is_free_installed()
	 * is_dash_logged_in()
	 * src_to_path()
	 * enqueue_admin_scripts()
	 * get_admin_capability()
	 * get_current_user_name()
	 * get_user_for_report()
	 * calculate_sum()
	 * format_bytes()
	 * format_interval()
	 * format_interval_hours()
	 * is_ajax_network_admin()
	 ***************************/

	/**
	 * Check if user is a paid one in WPMU DEV
	 *
	 * @return bool
	 */
	public static function is_member() {
		if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			if ( method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_projects' ) && method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_type' ) ) {
				$type     = WPMUDEV_Dashboard::$api->get_membership_type();
				$projects = WPMUDEV_Dashboard::$api->get_membership_projects();

				if ( ( 'unit' === $type && in_array( 1081721, $projects, true ) ) || ( 'single' === $type && 1081721 === $projects ) ) {
					return true;
				}

				if ( function_exists( 'is_wpmudev_member' ) ) {
					return is_wpmudev_member();
				}

				return false;
			}
		}

		return false;
	}

	/**
	 * Check if WPMU DEV Dashboard Plugin is logged in
	 *
	 * @return bool
	 */
	public static function is_dash_logged_in() {
		if ( ! class_exists( 'WPMUDEV_Dashboard' ) ) {
			return false;
		}

		if ( ! is_object( WPMUDEV_Dashboard::$api ) ) {
			return false;
		}

		if ( ! method_exists( WPMUDEV_Dashboard::$api, 'has_key' ) ) {
			return false;
		}

		return WPMUDEV_Dashboard::$api->has_key();
	}

	/**
	 * Try to cast a source URL to a path
	 *
	 * @param string $src  Source.
	 *
	 * @return string
	 */
	public static function src_to_path( $src ) {
		$path = wp_parse_url( $src );

		// Scheme will not be set on a URL.
		$url = isset( $path['scheme'] );

		if ( ! isset( $path['path'] ) ) {
			return '';
		}

		$path = ltrim( $path['path'], '/' );

		/**
		 * DOCUMENT_ROOT does not always store the correct path. For example, Bedrock appends /wp/ to the default dir.
		 * So if the source is a URL, we can safely use DOCUMENT_ROOT, else see if ABSPATH is defined.
		 */
		if ( $url ) {
			$path = path_join( $_SERVER['DOCUMENT_ROOT'], $path );
		} else {
			$root = defined( 'ABSPATH' ) ? ABSPATH : $_SERVER['DOCUMENT_ROOT'];
			$path = path_join( $root, $path );
		}

		$path = wp_normalize_path( $path );

		return apply_filters( 'wphb_src_to_path', $path, $src );
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @param int $ver Current version number of scripts.
	 */
	public static function enqueue_admin_scripts( $ver ) {
		wp_enqueue_script( 'wphb-admin', WPHB_DIR_URL . 'admin/assets/js/wphb-app.min.js', array( 'jquery', 'underscore' ), $ver, true );

		$last_report = Modules\Performance::get_last_report();
		if ( is_object( $last_report ) && isset( $last_report->data ) ) {
			$desktop_score = is_object( $last_report->data->desktop ) ? $last_report->data->desktop->score : '-';
			$mobile_score  = is_object( $last_report->data->mobile ) ? $last_report->data->mobile->score : '-';
		}

		$i10n = array(
			'cloudflare' => array(
				'is' => array(
					'connected' => self::get_module( 'cloudflare' )->is_connected() && self::get_module( 'cloudflare' )->is_zone_selected(),
				),
			),
			'nonces'     => array(
				'HBFetchNonce' => wp_create_nonce( 'wphb-fetch' ),
			),
			'strings'    => array(
				/* Performance test strings */
				'previousScoreMobile'    => isset( $mobile_score ) ? $mobile_score : '-',
				'previousScoreDesktop'   => isset( $desktop_score ) ? $desktop_score : '-',
				'removeButtonText'       => __( 'Remove', 'wphb' ),
				'youLabelText'           => __( 'You', 'wphb' ),
				'scanRunning'            => __( 'Running speed test...', 'wphb' ),
				'scanAnalyzing'          => __( 'Analyzing data and preparing report...', 'wphb' ),
				'scanWaiting'            => __( 'Test is taking a little longer than expected, hang in there…', 'wphb' ),
				'scanComplete'           => __( 'Test complete! Reloading...', 'wphb' ),
				/* Caching strings */
				'errorCachePurge'        => __( 'There was an error during the cache purge. Check folder permissions are 755 for /wp-content/wphb-cache or delete directory manually.', 'wphb' ),
				'successGravatarPurge'   => __( 'Gravatar cache purged.', 'wphb' ),
				'successPageCachePurge'  => __( 'Page cache purged.', 'wphb' ),
				'errorRecheckStatus'     => __( 'There was an error re-checking the caching status, please try again later.', 'wphb' ),
				'successRecheckStatus'   => __( 'Browser caching status updated.', 'wphb' ),
				'successCloudflarePurge' => __( 'Cloudflare cache successfully purged. Please wait 30 seconds for the purge to complete.', 'wphb' ),
				'successRedisPurge'      => __( 'Your cache has been cleared.', 'wphb' ),
				'selectZone'             => __( 'Select zone', 'wphb' ),
				/* Misc */
				'htaccessUpdated'        => __( 'Your .htaccess file has been updated', 'wphb' ),
				'htaccessUpdatedFailed'  => __( 'There was an error updating the .htaccess file', 'wphb' ),
				'errorSettingsUpdate'    => __( 'Error updating settings', 'wphb' ),
				'successUpdate'          => __( 'Settings updated', 'wphb' ),
				'deleteAll'              => __( 'Delete All', 'wphb' ),
				'db_delete'              => __( 'Are you sure you wish to delete', 'wphb' ),
				'db_entries'             => __( 'database entries', 'wphb' ),
				'db_backup'              => __( 'Make sure you have a current backup just in case.', 'wphb' ),
				'successRecipientAdded'  => __( ' has been added as a recipient but you still need to save your changes below to set this live.', 'wphb' ),
				'confirmRecipient'       => __( 'Your changes have been saved successfully. Any new recipients will receive an email shortly to confirm their subscription to these emails.', 'wphb' ),
				'awaitingConfirmation'   => __( 'Awaiting confirmation', 'wphb' ),
				'resendEmail'            => __( 'Resend email', 'wphb' ),
				'dismissLabel'           => __( 'Dismiss', 'wphb' ),
				'successAdvPurgeCache'   => __( 'Preload cache purged successfully.', 'wphb' ),
				'successAdvPurgeMinify'  => __( 'All database data and Custom Post Type information related to Asset Optimization has been cleared successfully.', 'wphb' ),
				'successAoOrphanedPurge' => __( 'Database entries removed successfully.', 'wphb' ),
				/* Cloudflare */
				'CloudflareHelpAPItoken' => __( 'Need help getting your API token?', 'wphb' ),
				'CloudflareHelpAPIkey'   => __( 'Need help getting your Global API key?', 'wphb' ),
			),
			'links'      => array(
				'audits'        => self::get_admin_menu_url( 'performance' ),
				'tutorials'     => self::get_admin_menu_url( 'tutorials' ),
				'disableUptime' => add_query_arg(
					array(
						'action'   => 'disable',
						'_wpnonce' => wp_create_nonce( 'wphb-toggle-uptime' ),
					),
					self::get_admin_menu_url( 'uptime' )
				),
				'resetSettings' => add_query_arg( 'wphb-clear', 'all', self::get_admin_menu_url() ),
			),
		);

		$minify_module = self::get_module( 'minify' );
		$is_scanning   = $minify_module->scanner->is_scanning();

		if ( $minify_module->is_on_page() || $is_scanning ) {
			$i10n = array_merge_recursive(
				$i10n,
				array(
					'minification' => array(
						'is'  => array(
							'scanning' => $is_scanning,
							'scanned'  => $minify_module->scanner->is_files_scanned(),
						),
						'get' => array(
							'currentScanStep' => $minify_module->scanner->get_current_scan_step(),
							'totalSteps'      => $minify_module->scanner->get_scan_steps(),
							'showCDNModal'    => ! is_multisite(),
							'showSwitchModal' => (bool) get_option( 'wphb-minification-show-config_modal' ),
						),
					),
					'strings'      => array(
						'discardAlert'  => __( 'Are you sure? All your changes will be lost', 'wphb' ),
						'queuedTooltip' => __( 'This file is queued for compression. It will get optimized when someone visits a page that requires it.', 'wphb' ),
						'excludeFile'   => __( "Don't load this file", 'wphb' ),
						'includeFile'   => __( 'Click to re-include', 'wphb' ),
						'falseMinify'   => __( 'Compression is off for this file. Turn it on to reduce its size.', 'wphb' ),
						'trueMinify'    => __( 'Compression is on for this file, which aims to reduce its size.', 'wphb' ),
						'falseCombine'  => __( 'Combine is off for this file. Turn it on to combine smaller files together.', 'wphb' ),
						'trueCombine'   => __( 'Combine is on for this file, which aims to reduce server requests.', 'wphb' ),
						'falseFooter'   => __( 'Move to footer is off for this file. Turn it on to load it from the footer.', 'wphb' ),
						'trueFooter'    => __( 'Move to footer is on for this file, which aims to speed up page load.', 'wphb' ),
						'falseInline'   => __( 'Inline CSS is off for this file. Turn it on to  add the style attributes to an HTML tag.', 'wphb' ),
						'trueInline'    => __( 'Inline CSS is on for this file, which will add the style attributes to an HTML tag.', 'wphb' ),
						'falseDefer'    => __( 'Click to turn on the force-loading of this file after the page has rendered.', 'wphb' ),
						'trueDefer'     => __( 'This file will be loaded only after the page has rendered.', 'wphb' ),
						'falseFont'     => __( 'Font optimization is off for this file. Turn it on to optimize it.', 'wphb' ),
						'trueFont'      => __( 'Font is optimized.', 'wphb' ),
						'truePreload'   => __( 'Preload is on for this file, which will download and cache the file so it is immediately available when the site is loaded.', 'wphb' ),
						'falsePreload'  => __( 'Preload is off for this file. Turn it on to download and cache the file so it is immediately available when the site is loaded.', 'wphb' ),
						'trueAsync'     => __( 'Async is enabled for this file, which will download the file asynchronously and execute it as soon as it’s ready. HTML parsing will be paused while the file is executed.', 'wphb' ),
						'falseAsync'    => __( 'Async is off for this file. Turn it on to download the file asynchronously and execute it as soon as it’s ready. HTML parsing will be paused while the file is executed.', 'wphb' ),
					),
					'links'        => array(
						'minification' => self::get_admin_menu_url( 'minification' ),
					),
				)
			);
		}

		if ( ! apply_filters( 'wpmudev_branding_hide_doc_link', false ) && $minify_module->is_on_page( true ) ) {
			wp_enqueue_script( 'wphb-react-tutorials', WPHB_DIR_URL . 'admin/assets/js/wphb-react-tutorials.min.js', array( 'wp-i18n' ), WPHB_VERSION, true );
		}

		global $wpdb, $wp_version;

		$i10n = array_merge_recursive(
			$i10n,
			array(
				'mixpanel' => array(
					'enabled'        => Settings::get_setting( 'tracking', 'settings' ),
					'plugin'         => 'Hummingbird',
					'plugin_type'    => self::is_member() ? 'pro' : 'free',
					'plugin_version' => WPHB_VERSION,
					'wp_version'     => $wp_version,
					'wp_type'        => is_multisite() ? 'multisite' : 'single',
					'locale'         => get_locale(),
					'active_theme'   => wp_get_theme()->get( 'Name' ),
					'php_version'    => PHP_VERSION,
					'mysql_version'  => $wpdb->db_version(),
					'server_type'    => Module_Server::get_server_type(),
				),
			)
		);

		wp_localize_script( 'wphb-admin', 'wphb', $i10n );
	}

	/**
	 * Returns Jed-formatted localization data
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_locale_data() {
		$translations = get_translations_for_domain( 'wphb' );

		$locale = array(
			'' => array(
				'domain' => 'wphb',
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}

	/**
	 * Return the needed capability for admin pages.
	 *
	 * @return string
	 */
	public static function get_admin_capability() {
		$cap = 'manage_options';

		if ( is_multisite() && is_network_admin() ) {
			$cap = 'manage_network';
		}

		return apply_filters( 'wphb_admin_capability', $cap );
	}

	/**
	 * Get Current username info
	 */
	public static function get_current_user_name() {
		$current_user = wp_get_current_user();

		if ( ! ( $current_user instanceof WP_User ) ) {
			return false;
		}

		if ( ! empty( $current_user->user_firstname ) ) { // First we try to grab user First Name.
			return $current_user->user_firstname;
		}

		return $current_user->user_nicename;
	}

	/**
	 * Get the default user data for the report.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	public static function get_user_for_report() {
		/** Current user @var WP_User $user */
		$user = wp_get_current_user();

		if ( empty( $user->first_name ) && empty( $user->last_name ) ) {
			$name = $user->user_login;
		} else {
			$name = $user->first_name . ' ' . $user->last_name;
		}

		return array(
			'name'  => $name,
			'email' => $user->user_email,
		);
	}

	/**
	 * This function will calculate the sum of file sizes in an array.
	 *
	 * We need this, because Asset Optimization module will store 'original_size' and 'compressed_size' values as
	 * strings, and such strings will contain &nbsp; instead of spaces, thus making it impossible to sum all the
	 * values with array_sum().
	 *
	 * @since 1.9.2
	 *
	 * @param array $arr  Array of items with sizes as strings.
	 *
	 * @return int|mixed
	 */
	public static function calculate_sum( $arr ) {
		$sum = 0;

		// Get separators from locale. Some Windows servers will return blank values.
		$locale        = localeconv();
		$thousands_sep = $locale['thousands_sep'] ?: ',';
		$decimal_point = $locale['decimal_point'] ?: '.';

		foreach ( $arr as $item => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}

			// Remove spaces.
			$sum += (float) str_replace(
				array( '&nbsp;', $thousands_sep, $decimal_point ),
				array( '', '', '.' ),
				$value
			);
		}

		return $sum;
	}

	/**
	 * Return the file size in a humanly readable format.
	 *
	 * Taken from http://www.php.net/manual/en/function.filesize.php#91477
	 *
	 * @since 2.0.0
	 *
	 * @param int $bytes      Number of bytes.
	 * @param int $precision  Precision.
	 *
	 * @return string
	 */
	public static function format_bytes( $bytes, $precision = 1 ) {
		$units  = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$bytes  = max( $bytes, 0 );
		$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow    = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}

	/**
	 * Convert seconds to a readable value.
	 *
	 * @since 2.0.0
	 *
	 * @param int $seconds  Number of seconds.
	 *
	 * @return string
	 */
	public static function format_interval( $seconds ) {
		if ( 3600 <= $seconds && 86400 > $seconds ) {
			return floor( $seconds / HOUR_IN_SECONDS ) . ' h';
		}

		if ( 86400 <= $seconds && 2419200 > $seconds ) {
			return floor( $seconds / DAY_IN_SECONDS ) . ' d';
		}

		if ( 2419200 <= $seconds && 31536000 > $seconds ) {
			return floor( $seconds / MONTH_IN_SECONDS ) . ' m';
		}

		if ( 31536000 < $seconds && 26611200 >= $seconds ) {
			return floor( $seconds / YEAR_IN_SECONDS ) . ' y';
		}

		return '-';
	}

	/**
	 * Format hours into days.
	 *
	 * @since 2.1.0
	 *
	 * @param int $hours  Number of hours.
	 *
	 * @return array
	 */
	public static function format_interval_hours( $hours ) {
		if ( $hours <= 24 ) {
			return array( $hours, 'hours' );
		}

		$days = floor( $hours / 24 );
		return array( $days, 'days' );
	}

	/**
	 *  Check if network admin.
	 *
	 * The is_network_admin() check does not work in AJAX calls.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_ajax_network_admin() {
		if ( ! is_multisite() ) {
			return false;
		}

		return defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_SERVER['HTTP_REFERER'] ) && preg_match( '#^' . network_admin_url() . '#i', wp_unslash( $_SERVER['HTTP_REFERER'] ) ); // Input var ok.
	}

	/***************************
	 *
	 * II. Layout functions
	 * get_servers_dropdown()
	 * get_caching_frequencies_dropdown()
	 * get_whitelabel_class()
	 ***************************/

	/**
	 * Get servers dropdown.
	 *
	 * @param bool|string $selected  Selected server.
	 */
	public static function get_servers_dropdown( $selected = false ) {
		$selected = $selected ? $selected : Module_Server::get_server_type();
		$disabled = is_multisite() && ! is_main_site();
		?>
		<select class="sui-select" name="wphb-server-type" id="wphb-server-type" class="server-type" <?php disabled( $disabled ); ?>>
			<?php foreach ( Module_Server::get_servers() as $server => $server_name ) : ?>
				<option value="<?php echo esc_attr( $server ); ?>" <?php selected( $server, $selected ); ?>>
					<?php
					if ( 'Apache/LiteSpeed' === $server_name ) {
						$server_name = 'Apache';
					}
					echo esc_html( $server_name );
					?>
				</option>
			<?php endforeach; ?>
			<option value="litespeed" <?php selected( 'litespeed', $selected ); ?>>
				Open LiteSpeed
			</option>
		</select>
		<?php
	}

	/**
	 * Prepare dropdown select with caching expiry settings.
	 *
	 * @param array $args        Arguments list.
	 * @param bool  $cloudflare  Get Cloudflare frequencies.
	 */
	public static function get_caching_frequencies_dropdown( $args = array(), $cloudflare = false ) {
		$defaults = array(
			'selected'  => false,
			'name'      => 'expiry-select',
			'id'        => false,
			'class'     => 'sui-select',
			'data-type' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! $args['id'] ) {
			$args['id'] = $args['name'];
		}

		if ( $cloudflare ) {
			$frequencies = Modules\Cloudflare::get_frequencies();
		} else {
			$frequencies = Modules\Caching::get_frequencies();
		}

		?>
		<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>" data-type="<?php echo esc_attr( $args['data-type'] ); ?>">
			<?php foreach ( $frequencies as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['selected'], $key ); ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Return rebranded class.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function get_whitelabel_class() {
		if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) {
			return '';
		}

		return apply_filters( 'wpmudev_branding_hero_image', '' ) ? 'sui-rebranded' : 'sui-unbranded';
	}

	/***************************
	 *
	 * III. Time and date functions
	 * human_read_time_diff()
	 * get_days_of_week()
	 * get_times()
	 ***************************/

	/**
	 * Credits to: http://stackoverflow.com/a/11389893/1502521
	 *
	 * @param int $seconds  Seconds.
	 *
	 * @return string
	 */
	public static function human_read_time_diff( $seconds ) {
		if ( ! $seconds ) {
			return __( 'Disabled', 'wphb' );
		}

		$minutes = 0;
		$hours   = 0;
		$days    = 0;
		$months  = 0;
		$years   = 0;

		while ( $seconds >= YEAR_IN_SECONDS ) {
			$years ++;
			$seconds = $seconds - YEAR_IN_SECONDS;
		}

		while ( $seconds >= MONTH_IN_SECONDS ) {
			$months ++;
			$seconds = $seconds - MONTH_IN_SECONDS;
		}

		while ( $seconds >= DAY_IN_SECONDS ) {
			$days ++;
			$seconds = $seconds - DAY_IN_SECONDS;
		}

		while ( $seconds >= HOUR_IN_SECONDS ) {
			$hours++;
			$seconds = $seconds - HOUR_IN_SECONDS;
		}

		while ( $seconds >= MINUTE_IN_SECONDS ) {
			$minutes++;
			$seconds = $seconds - MINUTE_IN_SECONDS;
		}

		$diff = new \stdClass();

		$diff->y = $years;
		$diff->m = $months;
		$diff->d = $days;
		$diff->h = $hours;
		$diff->i = $minutes;
		$diff->s = $seconds;

		if ( $diff->y || ( 11 === $diff->m && 30 <= $diff->d ) ) {
			$years = $diff->y;
			if ( 11 === $diff->m && 30 <= $diff->d ) {
				$years++;
			}
			/* translators: %d: year */
			$diff_time = sprintf( _n( '%d year', '%d years', $years, 'wphb' ), $years );
		} elseif ( $diff->m ) {
			/* translators: %d: month */
			$diff_time = sprintf( _n( '%d month', '%d months', $diff->m, 'wphb' ), $diff->m );
		} elseif ( $diff->d ) {
			/* translators: %d: day */
			$diff_time = sprintf( _n( '%d day', '%d days', $diff->d, 'wphb' ), $diff->d );
		} elseif ( $diff->h ) {
			/* translators: %d: hour */
			$diff_time = sprintf( _n( '%d hour', '%d hours', $diff->h, 'wphb' ), $diff->h );
		} elseif ( $diff->i ) {
			/* translators: %d: minute */
			$diff_time = sprintf( _n( '%d minute', '%d minutes', $diff->i, 'wphb' ), $diff->i );
		} else {
			/* translators: %d: second */
			$diff_time = sprintf( _n( '%d second', '%d seconds', $diff->s, 'wphb' ), $diff->s );
		}

		return $diff_time;
	}

	/**
	 * Get days of the week.
	 *
	 * @since 1.4.5
	 *
	 * @return mixed
	 */
	public static function get_days_of_week() {
		$timestamp = date_create( 'next Monday' );
		if ( 7 === get_option( 'start_of_week' ) ) {
			$timestamp = date_create( 'next Sunday' );
		}
		$days = array();
		for ( $i = 0; $i < 7; $i ++ ) {
			$days[]    = date_format( $timestamp, 'l' );
			$timestamp = date_modify( $timestamp, '+1 day' );
		}

		return apply_filters( 'wphb_scan_get_days_of_week', $days );
	}

	/**
	 * Return times frame for select box
	 *
	 * @since 1.4.5
	 *
	 * @return mixed
	 */
	public static function get_times() {
		$data = array();
		for ( $i = 0; $i < 24; $i ++ ) {
			foreach ( apply_filters( 'wphb_scan_get_times_interval', array( '00' ) ) as $min ) {
				$time          = $i . ':' . $min;
				$data[ $time ] = apply_filters( 'wphb_scan_get_times_hour_min', $time );
			}
		}

		return apply_filters( 'wphb_scan_get_times', $data );
	}

	/***************************
	 *
	 * IV. Link and url functions
	 * get_link()
	 * get_documentation_url()
	 * still_having_trouble_link()
	 * get_admin_menu_url()
	 * get_avatar_url()
	 ***************************/

	/**
	 * Return URL link.
	 *
	 * @param string $link_for Accepts: 'chat', 'plugin', 'support', 'smush', 'docs'.
	 * @param string $campaign  Utm campaign tag to be used in link. Default: 'hummingbird_pro_modal_upgrade'.
	 *
	 * @return string
	 */
	public static function get_link( $link_for, $campaign = 'hummingbird_pro_modal_upgrade' ) {
		$domain   = 'https://wpmudev.com';
		$wp_org   = 'https://wordpress.org';
		$utm_tags = "?utm_source=hummingbird&utm_medium=plugin&utm_campaign={$campaign}";

		switch ( $link_for ) {
			case 'configs':
				$link = "{$domain}/hub2/configs/my-configs";
				break;
			case 'hub-welcome':
				$link = "{$domain}/hub-welcome/{$utm_tags}";
				break;
			case 'chat':
				$link = "{$domain}/live-support/{$utm_tags}";
				break;
			case 'plugin':
				$link = "{$domain}/project/wp-hummingbird/{$utm_tags}";
				break;
			case 'support':
				if ( self::is_member() ) {
					$link = "{$domain}/hub2/support/#get-support";
				} else {
					$link = "{$wp_org}/support/plugin/hummingbird-performance";
				}
				break;
			case 'docs':
				$link = "{$domain}/docs/wpmu-dev-plugins/hummingbird/{$utm_tags}";
				break;
			case 'smush':
				if ( self::is_member() ) {
					// Return the pro plugin URL.
					$url  = WPMUDEV_Dashboard::$ui->page_urls->plugins_url;
					$link = $url . '#pid=912164';
				} else {
					// Return the free URL.
					$link = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=wp-smushit' ), 'install-plugin_wp-smushit' );
				}
				break;
			case 'smush-plugin':
				$link = "{$domain}/project/wp-smush-pro/{$utm_tags}";
				break;
			case 'hosting':
				$link = "{$domain}/hosting/{$utm_tags}";
				break;
			case 'wpmudev':
				$link = "{$domain}/{$utm_tags}";
				break;
			case 'tutorials':
				$link = "{$domain}/blog/tutorials/tutorial-category/hummingbird-pro/{$utm_tags}";
				break;
			default:
				$link = '';
				break;
		}

		return $link;
	}

	/**
	 * Get documentation URL.
	 *
	 * @since 1.7.0
	 *
	 * @param string $page  Page slug.
	 * @param string $view  View slug.
	 *
	 * @return string
	 */
	public static function get_documentation_url( $page, $view = '' ) {
		switch ( $page ) {
			case 'wphb-performance':
				if ( 'reports' === $view ) {
					$anchor = '#reporting';
				} elseif ( 'settings' === $view ) {
					$anchor = '#performance-test-settings';
				} else {
					$anchor = '#performance-test';
				}
				break;
			case 'wphb-caching':
				$anchor = '#caching';
				break;
			case 'wphb-gzip':
				$anchor = '#gzip-compression';
				break;
			case 'wphb-minification':
				$anchor = '#asset-optimization';
				break;
			case 'wphb-advanced':
				$anchor = '#advanced-tools';
				break;
			case 'wphb-uptime':
				$anchor = '#uptime';
				break;
			case 'wphb-settings':
				$anchor = '#settings';
				break;
			default:
				$anchor = '';
		}

		return 'https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/' . $anchor;
	}

	/**
	 * Display start a live chat link for pro user or open support ticket for non-pro user.
	 */
	public static function still_having_trouble_link() {
		esc_html_e( 'Still having trouble? ', 'wphb' );
		if ( self::is_member() && ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) :
			?>
			<a target="_blank" href="<?php echo esc_url( self::get_link( 'chat' ) ); ?>">
				<?php esc_html_e( 'Start a live chat.', 'wphb' ); ?>
			</a>
		<?php else : ?>
			<a target="_blank" href="<?php echo esc_url( self::get_link( 'support' ) ); ?>">
				<?php esc_html_e( 'Open a support ticket.', 'wphb' ); ?>
			</a>
			<?php
		endif;
	}

	/**
	 * Get url for plugin module page.
	 *
	 * @param string $page  Page.
	 *
	 * @return string
	 */
	public static function get_admin_menu_url( $page = '' ) {
		$hummingbird = WP_Hummingbird::get_instance();

		if ( is_object( $hummingbird->admin ) ) {
			$page_slug = empty( $page ) ? 'wphb' : 'wphb-' . $page;
			$page      = $hummingbird->admin->get_admin_page( $page_slug );
			if ( $page ) {
				return $page->get_page_url();
			}
		}

		return '';
	}

	/**
	 * Get avatar URL.
	 *
	 * @since 1.4.5
	 *
	 * @param string $get_avatar User email.
	 *
	 * @return mixed
	 */
	public static function get_avatar_url( $get_avatar ) {
		preg_match( "/src='(.*?)'/i", $get_avatar, $matches );

		return $matches[1];
	}

	/***************************
	 *
	 * V. Modules functions
	 * get_api()
	 * get_modules()
	 * get_module()
	 * get_active_cache_modules()
	 * get_number_of_issues()
	 * minified_files_count()
	 * remove_quick_setup()
	 ***************************/

	/**
	 * Get API.
	 *
	 * @return Api\API
	 */
	public static function get_api() {
		$hummingbird = WP_Hummingbird::get_instance();
		return $hummingbird->core->api;
	}

	/**
	 * Return the list of modules and their object instances
	 *
	 * Do not try to load before 'wp_hummingbird_loaded' action has been executed
	 *
	 * @return mixed
	 */
	private static function get_modules() {
		$hummingbird = WP_Hummingbird::get_instance();
		return $hummingbird->core->modules;
	}

	/**
	 * Get a module instance
	 *
	 * @param string $module Module slug.
	 *
	 * @return bool|Module|Modules\Page_Cache|Modules\GZip|Modules\Minify|Modules\Cloudflare|Modules\Uptime|Modules\Performance|Modules\Advanced|Modules\Redis|Modules\Caching
	 */
	public static function get_module( $module ) {
		$modules = self::get_modules();
		return isset( $modules[ $module ] ) ? $modules[ $module ] : false;
	}

	/**
	 * Return human readable names of active modules that have a cache.
	 *
	 * Checks Page, Gravatar & Asset Optimization.
	 *
	 * @return array
	 */
	public static function get_active_cache_modules() {
		$modules = array(
			'page_cache' => __( 'Page Cache', 'wphb' ),
			'cloudflare' => __( 'Cloudflare', 'wphb' ),
			'gravatar'   => __( 'Gravatar Cache', 'wphb' ),
			'minify'     => __( 'Asset Optimization Cache', 'wphb' ),
			'redis'      => __( 'Redis Cache', 'wphb' ),
		);

		$hb_modules = self::get_modules();

		foreach ( $modules as $module => $module_name ) {
			// If inactive, skip to next step.
			if ( 'cloudflare' !== $module && isset( $hb_modules[ $module ] ) && ! $hb_modules[ $module ]->is_active() ) {
				unset( $modules[ $module ] );
			}

			// Fix Cloudflare clear cache appearing on dashboard if it had been previously enabled but then uninstalled and reinstalled HB.
			// TODO: do we need this?
			if ( 'cloudflare' === $module && isset( $hb_modules[ $module ] ) && ! $hb_modules[ $module ]->is_connected() && ! $hb_modules[ $module ]->is_zone_selected() ) {
				unset( $modules[ $module ] );
			}
		}

		return $modules;
	}

	/**
	 * Get the number of issues for selected module
	 *
	 * @since 1.8.1 Added $report parameter.
	 *
	 * @param string     $module Module name.
	 * @param bool|array $report Current report.
	 *
	 * @return int
	 */
	public static function get_number_of_issues( $module, $report = false ) {
		$issues = 0;

		switch ( $module ) {
			case 'caching':
				$mod = self::get_module( $module );

				if ( ! $report ) {
					$mod->get_analysis_data();
					$report = $mod->status;
				}

				// No report - break.
				if ( ! $report ) {
					break;
				}

				$recommended = $mod->get_recommended_caching_values();
				foreach ( $report as $type => $value ) {
					$t = strtolower( $type );
					if ( empty( $value ) || $recommended[ $t ]['value'] > $value ) {
						$issues++;
					}
					unset( $t );
				}
				break;
			case 'gzip':
				if ( ! $report ) {
					$mod = self::get_module( $module );
					$mod->get_analysis_data();
					$report = $mod->status;
				}

				// No report - break.
				if ( ! $report ) {
					break;
				}

				$invalid = 0;
				foreach ( $report as $item => $type ) {
					if ( ! $type || 'privacy' === $type ) {
						$invalid++;
					}
				}

				$issues = $invalid;
				break;
		}

		return $issues;
	}

	/**
	 * Return the number of files used by minification.
	 *
	 * @since 1.4.5
	 *
	 * @param bool $only_minified  Only minified files.
	 *
	 * @return int
	 */
	public static function minified_files_count( $only_minified = false ) {
		$minify_module = self::get_module( 'minify' );

		// Get files count.
		$collection = $minify_module->get_resources_collection();
		// Remove those assets that we don't want to display.
		foreach ( $collection['styles'] as $key => $item ) {
			if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'styles' ) ) {
				unset( $collection['styles'][ $key ] );
			}

			// Keep only minified files.
			if ( $only_minified && ! preg_match( '/\.min\.(css|js)/', basename( $item['src'] ) ) ) {
				unset( $collection['styles'][ $key ] );
			}
		}
		foreach ( $collection['scripts'] as $key => $item ) {
			if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'scripts' ) ) {
				unset( $collection['scripts'][ $key ] );
			}

			// Kepp only minified files.
			if ( $only_minified && ! preg_match( '/\.min\.(css|js)/', basename( $item['src'] ) ) ) {
				unset( $collection['scripts'][ $key ] );
			}
		}

		return ( count( $collection['scripts'] ) + count( $collection['styles'] ) );
	}

	/**
	 * Returns a list of incompatible plugins if any
	 *
	 * @return array
	 */
	public static function get_incompat_plugin_list() {
		$plugins         = array();
		$caching_plugins = array(
			'autoptimize/autoptimize.php'               => 'Autoptimize',
			'litespeed-cache/litespeed-cache.php'       => 'LiteSpeed Cache',
			'speed-booster-pack/speed-booster-pack.php' => 'Speed Booster Pack',
			'swift-performance-lite/performance.php'    => 'Swift Performance Lite',
			'w3-total-cache/w3-total-cache.php'         => 'W3 Total Cache',
			'wp-fastest-cache/wpFastestCache.php'       => 'WP Fastest Cache',
			'wp-optimize/wp-optimize.php'               => 'WP-Optimize',
			'wp-performance-score-booster/wp-performance-score-booster.php' => 'WP Performance Score Booster',
			'wp-performance/wp-performance.php'         => 'WP Performance',
			'wp-super-cache/wp-cache.php'               => 'WP Super Cache',
		);

		foreach ( $caching_plugins as $plugin => $plugin_name ) {
			if ( is_plugin_active( $plugin ) ) {
				$plugins[ $plugin ] = $plugin_name;
			}
		}

		return $plugins;
	}

}
