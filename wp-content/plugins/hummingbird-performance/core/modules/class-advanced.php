<?php
/**
 * Class Advanced
 *
 * Implements various advanced features of the plugin: removing query strings from static resources,
 * removing the emojis file from rendering on the pages, prefetching dns queries.
 *
 * @since 1.8
 * @package Hummingbird\Core\Modules
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Traits\Module as ModuleContract;
use stdClass;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Advanced
 */
class Advanced extends Module {

	use ModuleContract;

	/**
	 * Initializes the module. Always executed even if the module is deactivated.
	 *
	 * Do not use __construct in subclasses, use init() instead
	 */
	public function init() {
		$options = $this->get_options();

		// See if we need to fetch the network value for query strings option.
		if ( ( $options['query_strings_global'] || $options['emoji_global'] ) && is_multisite() ) {
			$network_options = get_blog_option( get_main_site_id(), 'wphb_settings' );

			if ( $options['query_strings_global'] && isset( $network_options['advanced'] ) && isset( $network_options['advanced']['query_string'] ) ) {
				$options['query_string'] = $network_options['advanced']['query_string'];
			}
			if ( $options['emoji_global'] && isset( $network_options['advanced'] ) && isset( $network_options['advanced']['emoji'] ) ) {
				$options['emoji'] = $network_options['advanced']['emoji'];
			}
		}

		// Remove emoji.
		if ( $options['emoji'] ) {
			// Remove styles/scripts.
			$this->remove_emoji();
			// Remove dns prefetch.
			add_filter( 'emoji_svg_url', '__return_false' );
			// Remove from TinyMCE.
			add_filter( 'tiny_mce_plugins', array( $this, 'remove_emoji_tinymce' ) );
		}

		// Process HB cleanup task.
		add_action( 'wphb_hummingbird_cleanup', array( $this, 'hb_cleanup_cron' ) );

		// Ajax handler to return the comment template.
		add_action( 'wp_ajax_get_comments_template', array( $this, 'comment_template' ) );
		add_action( 'wp_ajax_nopriv_get_comments_template', array( $this, 'comment_template' ) );

		if ( isset( $options['lazy_load'] ) && $options['lazy_load']['enabled'] ) {
			/* Divi Compatibility  */
			add_filter( 'et_builder_load_requests', array( $this, 'add_divi_support' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_global' ) );
		}
		add_filter( 'paginate_links', array( $this, 'strip_params' ) );

		// Everything else is only for frontend.
		if ( is_admin() ) {
			return;
		}

		// Disable WooCommerce cart fragments.
		add_action( 'wp_enqueue_scripts', array( $this, 'remove_cart_fragments' ), 11 );

		// Remove query strings from static resources (only on front-end).
		if ( $options['query_string'] ) {
			add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 15, 1 );
			add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 15, 1 );
		}

		// DNS prefetch.
		add_filter( 'wp_resource_hints', array( $this, 'prefetch_dns' ), 10, 2 );

		// Preconnect.
		add_filter( 'wp_resource_hints', array( $this, 'add_preconnect_urls' ), 10, 2 );

		// Filter comment template if lazy load is enabled.
		if ( isset( $options['lazy_load'] ) && $options['lazy_load']['enabled'] ) {
			add_filter( 'comments_template', array( $this, 'filter_comments_template' ), 100 );
		}
	}

	/**
	 * *************************
	 * Remove query strings from static assets.
	 ***************************/

	/**
	 * Parse the src of script/style tags to remove the version query string.
	 *
	 * @param string $src  Script loader source path.
	 *
	 * @return string
	 */
	public function remove_query_strings( $src ) {
		$parts = preg_split( '/\?ver|\?timestamp/', $src );
		return $parts[0];
	}

	/**
	 * *************************
	 * Remove Emoji.
	 ***************************/

	/**
	 * Remove Emoji scripts from WordPress.
	 */
	public function remove_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	/**
	 * Remove Emoji icons from TinyMCE.
	 *
	 * @param array $plugins  An array of default TinyMCE plugins.
	 *
	 * @return array
	 */
	public function remove_emoji_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}

		return array();
	}

	/**
	 * *************************
	 * Prefetch DNS.
	 ***************************/

	/**
	 * Prefetch DNS. Minimum required WordPress version is 4.6.
	 *
	 * @param array  $hints          URLs to print for resource hints.
	 * @param string $relation_type  The relation type the URLs are printed for, e.g. 'preconnect' or 'prerender'.
	 *
	 * @see https://make.wordpress.org/core/2016/07/06/resource-hints-in-4-6/
	 *
	 * @return array
	 */
	public function prefetch_dns( $hints, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $hints;
		}

		$urls = Settings::get_setting( 'prefetch', 'advanced' );

		// If not urls set, return default WP hints array.
		if ( ! is_array( $urls ) || empty( $urls ) ) {
			return $hints;
		}

		$urls = array_map( 'esc_url', $urls );

		foreach ( $urls as $url ) {
			$hints[] = $url;
		}

		return $hints;
	}

	/**
	 * Optimize WooCommerce cart fragments.
	 *
	 * @since 2.2.0
	 */
	public function remove_cart_fragments() {
		$options = $this->get_options();

		if ( ! isset( $options['cart_fragments'] ) || ! $options['cart_fragments'] ) {
			return;
		}

		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		if ( 'all' === $options['cart_fragments'] || ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) ) {
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}

	/**
	 * Add preconnect resource hints to URLs.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $hints          URLs to print for resource hints.
	 * @param string $relation_type  The relation type the URLs are printed for, e.g. 'preconnect' or 'prerender'.
	 *
	 * @return array
	 */
	public function add_preconnect_urls( $hints, $relation_type ) {
		if ( 'preconnect' !== $relation_type ) {
			return $hints;
		}

		$urls = Settings::get_setting( 'preconnect', 'advanced' );

		// If not urls set, return default WP hints array.
		if ( ! is_array( $urls ) || empty( $urls ) ) {
			return $hints;
		}

		foreach ( $urls as $url ) {
			$attr = explode( ' ', $url );
			$hint = array(
				'href' => esc_url( $attr[0] ),
			);

			if ( isset( $attr[1] ) && 'crossorigin' === $attr[1] ) {
				$hint['crossorigin'] = '';
			}

			$hints[] = $hint;
		}

		return $hints;
	}

	/**
	 * *************************
	 * Database cleanup.
	 ***************************/

	/**
	 * Get default fields for database cleanup.
	 *
	 * @return array
	 */
	public static function get_db_fields() {
		return array(
			'revisions'          => array(
				'title'   => __( 'Post Revisions', 'wphb' ),
				'tooltip' => __( "Historic versions of your posts and pages. If you don't need to revert to older versions, delete these entries", 'wphb' ),
			),
			'drafts'             => array(
				'title'   => __( 'Draft Posts', 'wphb' ),
				'tooltip' => __( 'Auto-saved versions of your posts and pages. If you don’t use drafts you can safely delete these entries', 'wphb' ),
			),
			'trash'              => array(
				'title'   => __( 'Trashed Posts', 'wphb' ),
				'tooltip' => __( "Posts or pages you've marked as trash but haven't permanently deleted yet", 'wphb' ),
			),
			'spam'               => array(
				'title'   => __( 'Spam Comments', 'wphb' ),
				'tooltip' => __( "Comments marked as spam that haven't been deleted yet", 'wphb' ),
			),
			'trash_comment'      => array(
				'title'   => __( 'Trashed Comments', 'wphb' ),
				'tooltip' => __( "Comments you've marked as trash but haven't permanently deleted yet", 'wphb' ),
			),
			'expired_transients' => array(
				'title'   => __( 'Expired Transients', 'wphb' ),
				'tooltip' => __( 'Cached data that themes and plugins have stored, except these ones have expired and can be deleted', 'wphb' ),
			),
			'transients'         => array(
				'title'   => __( 'All Transients', 'wphb' ),
				'tooltip' => __( 'Cached data that themes and plugins have stored, but may still be in use. Note: the next page to load could take a bit longer due to WordPress regenerating transients.', 'wphb' ),
			),
		);
	}

	/**
	 * Get data from the database.
	 *
	 * @param string $type Accepts: 'revisions', 'drafts', 'trash', 'spam', 'trash_comment',
	 *                     'expired_transients', 'transients', 'all'.
	 *
	 * @return int|array|stdClass
	 */
	public static function get_db_count( $type = 'all' ) {
		global $wpdb;

		$count = wp_cache_get( "wphb_db_optimization:{$type}" );

		if ( false === $count ) {
			switch ( $type ) {
				case 'revisions':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_status = 'inherit'" ); // Db call ok.
					break;
				case 'drafts':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ( post_status = 'draft' OR post_status = 'auto-draft' ) AND ( post_type = 'page' OR post_type = 'post' )" ); // Db call ok.
					break;
				case 'trash':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ); // Db call ok.
					break;
				case 'spam':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ); // Db call ok.
					break;
				case 'trash_comment':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ); // Db call ok.
					break;
				case 'expired_transients':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\__%%' AND option_value < UNIX_TIMESTAMP()" ); // Db call ok.
					break;
				case 'transients':
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'" ); // Db call ok.
					break;
				case 'all':
				default:
					$count = $wpdb->get_row(
						"
					SELECT revisions, drafts, trash, spam, trash_comment, expired_transients, transients,
					       sum(revisions+drafts+trash+spam+trash_comment+expired_transients+transients) AS total
					FROM (
					  (SELECT
					    COUNT(CASE WHEN post_type = 'revision' AND post_status = 'inherit' THEN 1 ELSE NULL END) AS revisions,
					    COUNT(CASE WHEN ( post_status = 'draft' OR post_status = 'auto-draft' ) AND ( post_type = 'page' OR post_type = 'post' ) THEN 1 ELSE NULL END) AS drafts,
					    COUNT(CASE WHEN post_status = 'trash' THEN 1 ELSE NULL END) AS trash
					  FROM {$wpdb->posts}) as posts,
					  (SELECT
					    COUNT(CASE WHEN comment_approved = 'spam' THEN 1 ELSE NULL END) AS spam,
					    COUNT(CASE WHEN comment_approved = 'trash' THEN 1 ELSE NULL END) AS trash_comment
					  FROM {$wpdb->comments}) as comments,
					  (SELECT
					    COUNT(CASE WHEN option_name LIKE '\_transient\_timeout\__%%' AND option_value < UNIX_TIMESTAMP() THEN 1 ELSE NULL END ) AS expired_transients,
					    COUNT(CASE WHEN option_name LIKE '%_transient_%' THEN 1 ELSE NULL END) AS transients
					  FROM {$wpdb->options}) as options
					)"
					); // Db call ok.
					break;
			}

			wp_cache_set( "wphb_db_optimization:{$type}", $count );
		}

		return $count;
	}

	/**
	 * Delete database rows.
	 *
	 * @since 1.8
	 *
	 * @param string $type Accepts: 'revisions', 'drafts', 'trash', 'spam', 'trash_comment',
	 *                     'expired_transients', 'transients', 'all'.
	 *
	 * @return array|bool
	 */
	public function delete_db_data( $type ) {
		global $wpdb;

		$sql = array(
			'revisions'          => "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_status = 'inherit'",
			'drafts'             => "SELECT ID FROM {$wpdb->posts} WHERE ( post_status = 'draft' OR post_status = 'auto-draft' ) AND ( post_type = 'page' OR post_type = 'post' )",
			'trash'              => "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'",
			'spam'               => "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam'",
			'trash_comment'      => "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'trash'",
			'expired_transients' => "SELECT option_name FROM {$wpdb->options}
											WHERE option_name LIKE '\_transient\_timeout\__%%' AND option_value < UNIX_TIMESTAMP()",
			'transients'         => "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%_transient_%'",
		);

		if ( ! isset( $sql[ $type ] ) && 'all' !== $type ) {
			return false;
		}

		if ( 'all' === $type ) {
			$items = 0;
			foreach ( $sql as $type => $query ) {
				$items = $items + $this->delete( $query, $type );
			}
		} else {
			$items = $this->delete( $sql[ $type ], $type );
		}

		wp_cache_delete( "wphb_db_optimization:{$type}" );

		/**
		 * Fires after the database cleanup task.
		 *
		 * @since 1.9.2
		 *
		 * @param string $type   Data type that was cleared from the database. Can return following values: all,
		 *                       revisions, drafts, trash, spam, trash_comment, expired_transients, transients.
		 * @param int    $items  Number of items that was cleared from the database for the selected data type.
		 */
		do_action( 'wphb_delete_db_data', $type, $items );

		return array(
			'items' => $items,
			'left'  => self::get_db_count( 'all' ), // Check for any non-deleted items.
		);
	}

	/**
	 * Delete items from the database using a provided query and item type.
	 *
	 * @since 1.8
	 *
	 * @access private
	 * @param  string $sql   SQL query to fetch items.
	 * @param  string $type  Type of item to fetch.
	 *
	 * @return int
	 */
	private function delete( $sql, $type ) {
		global $wpdb;

		wp_cache_flush();
		$entries = $wpdb->get_col( $sql ); // Db call ok; no-cache oka.

		if ( 'revisions' === $type || 'drafts' === $type || 'trash' === $type ) {
			$func = 'wp_delete_post';
		} elseif ( 'spam' === $type || 'trash_comment' === $type ) {
			$func = 'wp_delete_comment';
		} elseif ( 'expired_transients' === $type && function_exists( 'delete_expired_transients' ) ) {
			delete_expired_transients();
			return count( $entries );
		} else {
			$func = 'delete_option';
		}

		$items = 0;
		foreach ( $entries as $entry ) {
			if ( 'delete_option' === $func ) {
				// No option to force delete in delete_option function.
				$del = call_user_func( $func, $entry );
			} else {
				// Force delete entries (without moving to trash).
				$del = call_user_func( $func, $entry, true );
			}

			if ( null !== $del && ! is_wp_error( $del ) ) {
				$items++;
			}
		}

		return $items;
	}

	/**
	 * *************************
	 * HB cleanup.
	 ***************************/

	/**
	 * Cleanup cron task.
	 *
	 * @since 1.8.1
	 */
	public function hb_cleanup_cron() {
		global $wpdb;

		// Select 100 entries.
		$entries = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wphb_minify_group' LIMIT 0, 100" ); // db call ok; no-cache ok.

		// Delete them properly.
		foreach ( $entries as $entry ) {
			if ( get_post( $entry ) && 'wphb_minify_group' === get_post_type( $entry ) ) {
				wp_delete_post( $entry, true );
			}
		}

		// Reschedule another batch if any entries left.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wphb_minify_group'" ); // db call ok; no-cache ok.

		if ( 0 < (int) $count ) {
			wp_schedule_single_event( time(), 'wphb_hummingbird_cleanup' );
		} else {
			wp_clear_scheduled_hook( 'wphb_hummingbird_cleanup' );
		}

		return true;
	}

	/**
	 * *************************
	 * System Information.
	 ***************************/

	/**
	 * Get PHP information for System Information.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public static function get_php_info() {
		$php_info = array();
		$php_vars = array(
			'max_execution_time',
			'open_basedir',
			'memory_limit',
			'upload_max_filesize',
			'post_max_size',
			'display_errors',
			'log_errors',
			'track_errors',
			'session.auto_start',
			'session.cache_expire',
			'session.cache_limiter',
			'session.cookie_domain',
			'session.cookie_httponly',
			'session.cookie_lifetime',
			'session.cookie_path',
			'session.cookie_secure',
			'session.gc_divisor',
			'session.gc_maxlifetime',
			'session.gc_probability',
			'session.referer_check',
			'session.save_handler',
			'session.save_path',
			'session.serialize_handler',
			'session.use_cookies',
			'session.use_only_cookies',
		);

		$php_info[ __( 'Version', 'wphb' ) ] = phpversion();
		foreach ( $php_vars as $setting ) {
			$php_info[ $setting ] = ini_get( $setting );
		}
		$levels          = array();
		$error_reporting = error_reporting();

		$extension_constants = array(
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_STRICT',
			'E_RECOVERABLE_ERROR',
			'E_DEPRECATED',
			'E_USER_DEPRECATED',
			'E_ALL',
		);

		foreach ( $extension_constants as $level ) {
			if ( defined( $level ) ) {
				$c = constant( $level );
				if ( $error_reporting & $c ) {
					$levels[ $c ] = $level;
				}
			}
		}
		$php_info[ __( 'Error Reporting', 'wphb' ) ] = implode( '<br>', $levels );
		$extensions                                  = get_loaded_extensions();
		natcasesort( $extensions );
		$php_info[ __( 'Extensions', 'wphb' ) ] = implode( '<br>', $extensions );

		return $php_info;
	}

	/**
	 * Get Database information for System Information.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public static function get_db_info() {
		global $wpdb;
		$dump_mysql = array();
		$mysql_vars = array(
			'key_buffer_size'    => true,   // Key cache size limit.
			'max_allowed_packet' => false,  // Individual query size limit.
			'max_connections'    => false,  // Max number of client connections.
			'query_cache_limit'  => true,   // Individual query cache size limit.
			'query_cache_size'   => true,   // Total cache size limit.
			'query_cache_type'   => 'ON',   // Query cache on or off.
		);
		$extra_info = array();
		$variables  = $wpdb->get_results( "SHOW VARIABLES WHERE Variable_name IN ( '" . implode( "', '", array_keys( $mysql_vars ) ) . "' )" ); // db call ok; no-cache ok.

		$dbh = $wpdb->dbh;
		if ( is_resource( $dbh ) ) {
			$driver  = 'mysql';
			$version = function_exists( 'mysqli_get_server_info' ) ? mysqli_get_server_info( $dbh ) : mysql_get_server_info( $dbh );
		} elseif ( is_object( $dbh ) ) {
			$driver = get_class( $dbh );
			if ( method_exists( $dbh, 'db_version' ) ) {
				$version = $dbh->db_version();
			} elseif ( isset( $dbh->server_info ) ) {
				$version = $dbh->server_info;
			} elseif ( isset( $dbh->server_version ) ) {
				$version = $dbh->server_version;
			} else {
				$version = __( 'Unknown', 'wphb' );
			}
			if ( isset( $dbh->client_info ) ) {
				$extra_info['Driver version'] = $dbh->client_info;
			}
			if ( isset( $dbh->host_info ) ) {
				$extra_info['Connection info'] = $dbh->host_info;
			}
		} else {
			$version = $driver = __( 'Unknown', 'wphb' );
		}
		$extra_info['Database']     = $wpdb->dbname;
		$extra_info['Charset']      = $wpdb->charset;
		$extra_info['Collate']      = $wpdb->collate;
		$extra_info['Table Prefix'] = $wpdb->prefix;

		$dump_mysql['Server Version'] = $version;
		$dump_mysql['Driver']         = $driver;
		foreach ( $extra_info as $key => $val ) {
			$dump_mysql[ $key ] = $val;
		}
		foreach ( $mysql_vars as $key => $val ) {
			$dump_mysql[ $key ] = $val;
		}
		foreach ( $variables as $item ) {
			if ( is_numeric( $item->Value ) && ( $item->Value >= ( 1024 * 1024 ) ) ) {
				$val = size_format( $item->Value );
			}
			$dump_mysql[ $item->Variable_name ] = $val;
		}

		return $dump_mysql;
	}

	/**
	 * Get WordPress Installation information for System Information.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public static function get_wp_info() {
		global $wp_version;
		$dump_wp                      = array();
		$wp_consts                    = array(
			'ABSPATH',
			'WP_CONTENT_DIR',
			'WP_PLUGIN_DIR',
			'WPINC',
			'WP_LANG_DIR',
			'UPLOADBLOGSDIR',
			'UPLOADS',
			'WP_TEMP_DIR',
			'SUNRISE',
			'WP_ALLOW_MULTISITE',
			'MULTISITE',
			'SUBDOMAIN_INSTALL',
			'DOMAIN_CURRENT_SITE',
			'PATH_CURRENT_SITE',
			'SITE_ID_CURRENT_SITE',
			'BLOGID_CURRENT_SITE',
			'BLOG_ID_CURRENT_SITE',
			'COOKIE_DOMAIN',
			'COOKIEPATH',
			'SITECOOKIEPATH',
			'DISABLE_WP_CRON',
			'ALTERNATE_WP_CRON',
			'DISALLOW_FILE_MODS',
			'WP_HTTP_BLOCK_EXTERNAL',
			'WP_ACCESSIBLE_HOSTS',
			'WP_DEBUG',
			'WP_DEBUG_LOG',
			'WP_DEBUG_DISPLAY',
			'ERRORLOGFILE',
			'SCRIPT_DEBUG',
			'WP_LANG',
			'WP_MAX_MEMORY_LIMIT',
			'WP_MEMORY_LIMIT',
			'WPMU_ACCEL_REDIRECT',
			'WPMU_SENDFILE',
		);
		$dump_wp['WordPress Version'] = $wp_version;
		foreach ( $wp_consts as $const ) {
			$dump_wp[ $const ] = self::format_constant( $const );
		}

		return $dump_wp;
	}


	/**
	 * Get server information for System Information.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public static function get_server_info() {
		$dump_server = array();
		$server      = explode( ' ', wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ); // Input var ok.
		$server      = explode( '/', reset( $server ) );

		if ( isset( $server[1] ) ) {
			$server_version = $server[1];
		} else {
			$server_version = 'Unknown';
		}

		$dump_server[ __( 'Software Name', 'wphb' ) ]     = $server[0];
		$dump_server[ __( 'Software Version', 'wphb' ) ]  = $server_version;
		$dump_server[ __( 'Server IP', 'wphb' ) ]         = isset( $_SERVER['SERVER_ADDR'] ) ? wp_unslash( $_SERVER['SERVER_ADDR'] ) : __( 'undefined', 'wphb' );
		$dump_server[ __( 'Server Hostname', 'wphb' ) ]   = isset( $_SERVER['SERVER_NAME'] ) ? wp_unslash( $_SERVER['SERVER_NAME'] ) : __( 'undefined', 'wphb' );
		$dump_server[ __( 'Server Admin', 'wphb' ) ]      = isset( $_SERVER['SERVER_ADMIN'] ) ? wp_unslash( $_SERVER['SERVER_ADMIN'] ) : __( 'undefined', 'wphb' );
		$dump_server[ __( 'Server local time', 'wphb' ) ] = date( 'Y-m-d H:i:s (\U\T\C P)' );
		$dump_server[ __( 'Operating System', 'wphb' ) ]  = @php_uname( 's' );
		$dump_server[ __( 'OS Hostname', 'wphb' ) ]       = @php_uname( 'n' );
		$dump_server[ __( 'OS Version', 'wphb' ) ]        = @php_uname( 'v' );

		return $dump_server;
	}

	/**
	 * Helper function.
	 *
	 * @since 1.8.2
	 *
	 * @param string $constant  Name of a PHP const.
	 *
	 * @return string
	 */
	private static function format_constant( $constant ) {
		if ( ! defined( $constant ) ) {
			return '<em>' . __( 'undefined', 'wphb' ) . '</em>';
		}

		$val = constant( $constant );
		if ( ! is_bool( $val ) ) {
			return $val;
		} elseif ( ! $val ) {
			return __( 'FALSE', 'wphb' );
		} else {
			return __( 'TRUE', 'wphb' );
		}
	}

	/**
	 * Get orphaned mata rows from `wp_postmeta` that, most likely, belong to Asset Optimization, but
	 * do not have a registered `wphb_minify_group` in the `wp_postmeta` table.
	 *
	 * @since 2.7.0
	 *
	 * @return int
	 */
	public function get_orphaned_ao() {
		$count = wp_cache_get( 'wphb_ao_meta_fields' );

		if ( false === $count ) {
			global $wpdb;

			$table  = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'postmeta';
			$search = implode( "', '", Minify::get_postmeta_fields() );

			$results = $wpdb->get_row(
			"SELECT COUNT( post_id ) as posts FROM {$table} WHERE meta_key IN ('{$search}');"
			); // Db call ok.

			$count = $results->posts;
			unset( $results );
		}

		wp_cache_set( 'wphb_ao_meta_fields', $count );

		return $count;
	}

	/**
	 * Pluck all orphaned meta fields that belong to Hummingbird.
	 *
	 * Difference from above method, it will only include actual orphaned data, while with the method
	 * above, we need to subtract the number of valid assets.
	 *
	 * @since 2.7.0
	 *
	 * @return int.
	 */
	public function get_orphaned_ao_complex() {
		$count = wp_cache_get( 'wphb_ao_orphaned_data' );

		if ( false === $count ) {
			global $wpdb;

			$search_fields   = implode( "', '", Minify::get_postmeta_fields() );
			$database_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );
			$posts_table     = $database_prefix . 'posts';
			$post_meta_table = $database_prefix . 'postmeta';

			$count = $wpdb->get_var(
				"SELECT COUNT( post_id ) FROM {$post_meta_table} A
                LEFT JOIN {$posts_table} B
				ON A.post_id = B.ID
				WHERE A.meta_key IN ('{$search_fields}')
				AND B.ID IS NULL"
			); // Db call ok.
		}

		wp_cache_set( 'wphb_ao_orphaned_data', $count );

		return $count;
	}

	/**
	 * Clear out a set number of orphaned asset optimization data.
	 *
	 * @since 2.7.0
	 *
	 * @param int $rows  Number of rows to clear.
	 */
	public function purge_orphaned_step( $rows ) {
		global $wpdb;

		$search_fields   = implode( "', '", Minify::get_postmeta_fields() );
		$database_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );
		$posts_table     = $database_prefix . 'posts';
		$post_meta_table = $database_prefix . 'postmeta';

		$items = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_id FROM {$post_meta_table} A
                LEFT JOIN {$posts_table} B
				ON A.post_id = B.ID
				WHERE A.meta_key IN ('{$search_fields}')
				AND B.ID IS NULL LIMIT %d",
				$rows
			)
		); // Db call ok.

		$ids = implode( ',', array_map( 'intval', $items ) );
		$wpdb->query( "DELETE FROM {$post_meta_table} WHERE meta_id IN($ids)" );

		// Remove count cache.
		wp_cache_delete( 'wphb_ao_meta_fields' );

		/** This might be a better alternative - just try to force purge everything.
		$sql = "DELETE A FROM {$post_meta_table} AS A
				LEFT JOIN {$posts_table} AS B ON A.post_id = B.ID
				WHERE A.meta_key IN ('{$search_fields}')
				AND B.ID IS NULL;";
		*/
	}

	/**
	 * *************************
	 * Comment lazy loading.
	 ***************************/

	/**
	 * Enqueue lazy load scripts on single page/post.
	 *
	 * @since 2.5.0
	 */
	public function enqueue_global() {
		$lazy_load_comment_js = is_singular();
		$lazy_load_comment_js = apply_filters( 'wphb_lazy_load_comment_js', $lazy_load_comment_js );
		// Do not load sitewide.
		if ( ! $lazy_load_comment_js ) {
			return;
		}

		wp_enqueue_script(
			'wphb-lazy-load',
			WPHB_DIR_URL . 'admin/assets/js/wphb-lazy-load.min.js',
			array(),
			WPHB_VERSION,
			true
		);

		wp_localize_script(
			'wphb-lazy-load',
			'wphbGlobal',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Checks for the user agent to validate if the visitor is a bot
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	public function visitor_is_a_bot() {
		/* No User agent, Or the useragent string matches the basic pattern */
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || preg_match( '/bot|crawl|slurp|spider/i', sanitize_key( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates if we should be lazy loading comments or not
	 *
	 * @since 2.5.0
	 *
	 * @param array $options plugin settings.
	 *
	 * @return bool True|False
	 */
	public function should_lazy_load( $options ) {
		global $wp_query;

		$lazy_load = true;

		if ( $this->visitor_is_a_bot() ) {
			$lazy_load = false;
		}

		$comment_count = get_comments_number();

		if ( ! $comment_count ) {
			// If there are no comments.
			$lazy_load = false;
		} elseif ( (int) $options['lazy_load']['threshold'] > $comment_count ) {
			// if threshold is lower than total comment count.
			$lazy_load = false;
		} elseif ( ! empty( $wp_query->comment_count ) && (int) $options['lazy_load']['threshold'] > $wp_query->comment_count ) {
			// If there is a comment pagination, and the comment_count for the page < threshold.
			$lazy_load = false;
		}

		return $lazy_load;
	}

	/**
	 * Filters the default comment template to Lazy Load markup ( Button or a Scroll div )
	 *
	 * @since 2.5.0
	 *
	 * @param string $template Current comment template.
	 *
	 * @return string HTML markup for the button or a div used to load comments template on scroll
	 */
	public function filter_comments_template( $template ) {
		$options = $this->get_options();

		$should_lazy_load = $this->should_lazy_load( $options );

		/* Return the original template if we should not lazy load */
		if ( ! apply_filters( 'wphb_should_lazy_load_comment', $should_lazy_load ) ) {
			return $template;
		}

		/* Set "separate-comments" transient if not set already */
		if ( false === get_transient( 'wphb-separate-comments' ) ) {
			global $wp_query;

			// Check if separate comments is set.
			$separate_comments = ! empty( $wp_query ) && ! empty( $wp_query->comments_by_type ) ? 1 : 0;

			set_transient( 'wphb-separate-comments', $separate_comments, 60 );
		}

		set_query_var( 'lazy_load_settings', $options['lazy_load'] );

		return WPHB_DIR_PATH . 'core/views/comment-template.php';
	}

	/**
	 * Strip parameters.
	 *
	 * @since 2.5.0
	 *
	 * @param string $link  URL.
	 *
	 * @return bool|string
	 */
	public function strip_params( $link ) {
		if ( ! isset( $_GET['action'] ) || 'get_comments_template' !== sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			return $link;
		}

		return remove_query_arg( array( 'action', 'id', '_nonce' ), $link );
	}

	/**
	 * Ajax handler to load the actual comment template on front-end.
	 *
	 * @since 2.5.0
	 */
	public function comment_template() {
		if ( empty( $_GET['_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_nonce'] ), 'comments_template' ) ) {
			$message = '<div class="wphb-lazy-load-error sui-error">' . esc_html__( 'We could not validate the request, try reloading the page.', 'wphb' ) . '</div>';
			wp_send_json_error( array( 'content' => $message ) );
		}

		/* Return an error if post id isn't set */
		if ( empty( $_GET['id'] ) ) {
			$message = '<div class="wphb-lazy-load-error sui-error">' . esc_html__( 'Something went wrong. Try reloading the page to see if the comments load for you.', 'wphb' ) . '</div>';
			wp_send_json_error( array( 'content' => $message ) );
		}

		$cpage_num = ! empty( $_GET['cpage_num'] ) ? absint( $_GET['cpage_num'] ) : '';

		$separate_comments = get_transient( 'wphb-separate-comments' );

		/* Remove our comment template filter to be able to get the original content */
		remove_filter( 'comments_template', array( $this, 'filter_comments_template' ), 100 );

		/**
		 * Allow plugin/themes to hook-in, to enable support for loading custom comments template over AJAX
		 */
		do_action( 'wphb_ajax_get_comments_template' );

		query_posts( array( 'p' => absint( $_GET['id'] ) ) );

		/* Restore original Post Data */
		wp_reset_postdata();
		if ( have_posts() ) {
			the_post();

			// Workaround :( .
			$orig_uri               = $_SERVER['REQUEST_URI'];
			$_SERVER['REQUEST_URI'] = get_the_permalink( get_the_ID() );
			if ( ! empty( $cpage_num ) ) {
				set_query_var( 'cpage', $cpage_num );
				/**
				 * Override page_comments decision set from discussion settings page.
				 * By default comment pagination is ignored when page_comments is false.
				 * See in function comments_template() in wp-includes/comment-template.php
				 * We need it always true to get comments for specific cpage.
				 */
				add_filter( 'option_page_comments', array( $this, 'filter_option_page_comments' ), 100 );

				/**
				 * As we override page_comments decision, function comments_template() calculate max_num_comment_pages and set to wp_query
				 * Theme comment template tries to generate comment pagination by
				 * using the_comments_pagination/get_the_comments_pagination function
				 * This function needs comment page count. If comment page count is more than 1 , it generates pagination
				 * get_comment_pages_count() function usages max_num_comment_pages
				 * We don't want to show pagination, We will reset max_num_comment_pages value to 1 so that get_comment_pages_count retuns 1
				 * comments_template function usages a filter for comments_template file.
				 * We will use this filter to reset wp_query->max_num_comment_pages.
				 */
				add_filter( 'comments_template', array( $this, 'reset_wp_query_max_num_comment_pages' ), 1 );

				/**
				 * Override comment_order decision set from discussion settings page.
				 * When default_comments_page value is 'newest' we want comment_order 'desc'.
				 * When default_comments_page value is 'oldest' we want comment_order 'asc'.
				 */
				add_filter( 'option_comment_order', array( $this, 'filter_option_comment_order' ), 100 );

			}
			ob_start();

			comments_template( '', $separate_comments );
			$content = ob_get_contents();

			ob_end_clean();
			$_SERVER['REQUEST_URI'] = $orig_uri;
		}

		if ( ! empty( $content ) ) {
			wp_send_json_success( array( 'content' => $content ) );
		}

		die();
	}

	/**
	 * Add Lazy load action to the list of supported ajax action in Divi theme comment template.
	 *
	 * @since 2.5.0
	 *
	 * @return array
	 */
	public function add_divi_support() {
		return array( 'action' => array( 'get_comments_template' ) );
	}

	/**
	 * Set page_comments option true.
	 *
	 * Return TRUE only one time when the filter option_page_comments called in
	 * function comments_template() in wp-includes/comment-template.php
	 *
	 * @since 2.5.0
	 *
	 * @param mixed $value  Value.
	 *
	 * @return bool|mixed
	 */
	public function filter_option_page_comments( $value ) {
		static $n;

		if ( ! empty( $n ) ) {
			return $value;
		}

		$n = 1;

		return true;
	}

	/**
	 * This is a fake callback function for comments_template filter
	 * We will not change $template value
	 * We will change max_num_comment_pages var from $wp_query object
	 *
	 * @since 2.5.0
	 *
	 * @param string $template The path to the theme template file.
	 *
	 * @return string
	 */
	public function reset_wp_query_max_num_comment_pages( $template ) {
		global $wp_query;
		$wp_query->max_num_comment_pages = 1;
		return $template;
	}

	/**
	 * Filter comment_order option value.
	 * When default_comments_page value is 'newest' we want comment_order 'desc'.
	 * When default_comments_page value is 'oldest' we want comment_order 'asc'.
	 *
	 * @since 2.5.0
	 *
	 * @param string $order  Order type.
	 *
	 * @return string
	 */
	public function filter_option_comment_order( $order ) {
		$dcp = get_option( 'default_comments_page' );
		return ( 'newest' === $dcp ) ? 'desc' : 'asc';
	}

}
