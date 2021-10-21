<?php
/**
 * Page caching class.
 *
 * For easier code maintenance and support, class is split into sections:
 * I.   INIT FUNCTIONS
 * II.  HELPER FUNCTIONS
 * III. FILESYSTEM FUNCTIONS
 * IV.  CACHE CONTROL FUNCTIONS
 * V.   ACTIONS AND FILTERS
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Installer;
use Hummingbird\Core\Module;
use Hummingbird\Core\Modules\Caching\Preload;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use stdClass;
use WP_Error;
use WP_Post;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is a compatibility check to support pre 2.5 versions upgrading to 2.5+.
 * Because in 2.5 we've added traits, but the advanced-cache.php was flawed and did not allow for a proper upgrade.
 *
 * @since 2.5.0
 * @todo Remove once the upgrade_2_5_0() is removed (approximately in version 2.9/3.0).
 * @see Installer::upgrade_2_5_0()
 */
if ( ! trait_exists( '\Hummingbird\Core\Traits\WPConfig' ) && isset( $plugin_path ) ) {
	$trait = $plugin_path . 'core/traits/trait-wpconfig.php';
	if ( file_exists( $trait ) ) {
		include_once $plugin_path . 'core/traits/trait-wpconfig.php';
	}
}

/**
 * Class Page_Cache
 *
 * @since 1.7.0
 */
class Page_Cache extends Module {

	use \Hummingbird\Core\Traits\WPConfig;

	/**
	 * Last error.
	 *
	 * @since 1.7.0
	 * @var   WP_Error $error
	 */
	public $error = false;

	/**
	 * Start time when caching a file.
	 * Used for calculating the amount of time it takes to build the cached file.
	 *
	 * @since 1.7.0
	 * @var   int $start_time
	 */
	private $start_time;

	/**
	 * List of mobile user agents.
	 *
	 * Props to WP Super Cache
	 *
	 * @since 2.1.0
	 * @var array $mobile_agents
	 */
	private static $mobile_agents = array( 'Android', '2.0 MMP', '240x320', 'AvantGo', 'BlackBerry', 'Blazer', 'Cellphone', 'Danger', 'DoCoMo', 'Elaine/3.0', 'EudoraWeb', 'hiptop', 'IEMobile', 'iPhone', 'iPod', 'KYOCERA/WX310K', 'LG/U990', 'MIDP-2.0', 'MMEF20', 'MOT-V', 'NetFront', 'Newt', 'Nintendo Wii', 'Nitro', 'Nokia', 'Opera Mini', 'Palm', 'Playstation Portable', 'portalmmm', 'Proxinet', 'ProxiNet', 'SHARP-TQ-GX10', 'Small', 'SonyEricsson', 'Symbian OS', 'SymbianOS', 'TS21i-10', 'UP.Browser', 'UP.Link', 'Windows CE', 'WinWAP' );

	/**
	 * Execute module actions.
	 *
	 * @since 1.7.0
	 */
	public function run() {
		// Init modules and perform pre-run checks.
		$this->init_filesystem();
		$this->check_plugin_compatibility();
		$this->check_minification_queue();

		add_action( 'admin_init', array( $this, 'maybe_update_advanced_cache' ) );

		add_action( 'init', array( $this, 'init_preloader' ) );
		// Preload page cache on post/page update.
		add_action( 'wphb_page_cache_preload_page', array( new Preload(), 'preload_page_on_purge' ) );

		/**
		 * Trigger a cache clear.
		 *
		 * If post ID is set, will try to clear cache for that page or post with all the related
		 * taxonomies (tags, category and author pages).
		 *
		 * @since 1.9.2
		 *
		 * @param int $post_id  Post ID.
		 */
		add_action( 'wphb_clear_page_cache', array( $this, 'clear_cache_action' ) );
		add_action( 'wphb_clear_cache_url', array( $this, 'clear_external_cache' ) );

		// Post status transitions.
		add_action( 'edit_post', array( $this, 'post_edit' ), 0 );
		add_action( 'transition_post_status', array( $this, 'post_status_change' ), 10, 3 );

		// Clear cache button on edit post page.
		add_action( 'post_submitbox_misc_actions', array( $this, 'clear_cache_button' ) );
		add_filter( 'post_updated_messages', array( $this, 'clear_cache_message' ) );

		// Clear cache on new comment.
		add_action( 'comment_post', array( $this, 'clear_on_comment_post' ), 10, 3 );

		// Only cache pages when there are no errors.
		if ( ! is_wp_error( $this->error ) ) {
			$this->init_caching();
		}
	}

	/**
	 * Initialize module.
	 *
	 * @since 1.7.0
	 */
	public function init() {
		add_filter( 'wp_hummingbird_is_active_module_page_cache', array( $this, 'module_status' ) );
	}

	/**
	 * Activate page cache.
	 *
	 * @since   1.7.0
	 * @aince   1.8.0  Changed access to private
	 * @access  private
	 * @used-by Page_Cache::toggle_service()
	 */
	private function activate() {
		if ( $this->check_wp_settings( true ) ) {
			$this->init_filesystem();
			$this->wpconfig_add( 'WP_CACHE', true );
		}
	}

	/**
	 * Enable page cache module.
	 *
	 * @since 1.9.0
	 *
	 * @used-by \Hummingbird\Admin\Pages\Caching::trigger_load_action()
	 * @used-by \Hummingbird\Core\Api\Hub::action_enable()
	 */
	public function enable() {
		$this->toggle_service( true, true );
	}

	/**
	 * Disable page cache module.
	 *
	 * @since 1.9.0
	 *
	 * @used-by \Hummingbird\Admin\Pages\Caching::trigger_load_action()
	 * @used-by \Hummingbird\Core\Api\Hub::action_disable()
	 */
	public function disable() {
		$this->toggle_service( false, true );
	}

	/**
	 * *************************
	 * I. INIT FUNCTIONS
	 *
	 * Available methods:
	 * check_plugin_compatibility()
	 * check_minification_queue()
	 * init_filesystem()
	 * init_preloader()
	 * maybe_update_advanced_cache()
	 ***************************/

	/**
	 * Check for other caching plugins.
	 * Add error if incompatible plugin detected.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::init()
	 */
	private function check_plugin_compatibility() {
		if ( is_wp_error( $this->error ) || ! $this->is_active() ) {
			return;
		}

		$caching_plugins = array(
			'autoptimize/autoptimize.php'               => 'Autoptimize',
			'litespeed-cache/litespeed-cache.php'       => 'LiteSpeed Cache',
			'speed-booster-pack/speed-booster-pack.php' => 'Speed Booster Pack',
			'swift-performance-lite/performance.php'    => 'Swift Performance Lite',
			'w3-total-cache/w3-total-cache.php'         => 'W3 Total Cache',
			'wp-fastest-cache/wpFastestCache.php'       => 'WP Fastest Cache',
			'wp-optimize/wp-optimize.php'               => 'WP-Optimize',
			'wp-performance-score-booster'              => 'WP Performance Score Booster',
			'wp-performance/wp-performance.php'         => 'WP Performance',
			'wp-super-cache/wp-cache.php'               => 'WP Super Cache',
		);

		foreach ( $caching_plugins as $plugin => $plugin_name ) {
			if ( in_array( $plugin, get_option( 'active_plugins', array() ), true ) ) {
				$this->error = new WP_Error(
					'caching-plugin-detected',
					/* translators: %s: plugin name. */
					sprintf( __( '%s plugin detected. Please disable it to use Hummingbird page caching functionality.', 'wphb' ), $plugin_name )
				);
				break;
			}
		}

		// See if there's already an advanced-cache.php file in place.
		$adv_cache_file = dirname( get_theme_root() ) . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) && false === strpos( file_get_contents( $adv_cache_file ), 'WPHB_ADVANCED_CACHE' ) ) {
			$this->error = new WP_Error(
				'advanced-cache-detected',
				sprintf( /* translators: %1$s - opening a tag, %2$s - closing a tag, %3$s - button tag, %4$s - closing button tag */
					__( 'Hummingbird has detected an advanced-cache.php file in your site’s wp-content directory. %1$sManage your plugins%2$s and disable any other active caching plugins to ensure Hummingbird’s page caching works properly.<br>If no other caching plugins are active, the advanced-cache.php may have been left by a previously used caching plugin. You can remove the file from the wp-content directory, or remove it via your file manager or FTP.%3$sRemove file%4$s', 'wphb' ),
					'<a href="' . esc_url( network_admin_url( 'plugins.php' ) ) . '">',
					'</a>',
					'<br><button id="wphb-remove-advanced-cache" style="margin-top: 10px" class="sui-button sui-button-blue" role="button">',
					'</button>'
				)
			);
		}
	}

	/**
	 * Check for active minification queue.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::init()
	 */
	private function check_minification_queue() {
		if ( is_wp_error( $this->error ) || ! $this->is_active() ) {
			return;
		}

		if ( get_transient( 'wphb-processing' ) ) {
			$this->error = new WP_Error(
				'min-queue-present',
				__( 'Hummingbird has halted page caching to prevent any issues while asset optimization is in progress. Page caching will resume automatically when asset optimization is complete.', 'wphb' )
			);
		}
	}

	/**
	 * Init filesystem.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::init()
	 */
	private function init_filesystem() {
		// If module not active - return.
		if ( ! $this->is_active() ) {
			return;
		}

		// If there's an error (except not found WP_CACHE constant) - return.
		if ( is_wp_error( $this->error ) && 'no-wp-cache-constant' !== $this->error->get_error_code() ) {
			return;
		}

		// Init filesystem.
		global $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		if ( is_wp_error( $wphb_fs->status ) ) {
			$this->error = $wphb_fs->status;
			return;
		}

		// See if there's already an advanced-cache.php file in place.
		$adv_cache_file_dest = dirname( get_theme_root() ) . '/advanced-cache.php';
		if ( ! file_exists( $adv_cache_file_dest ) ) {
			// Try to add advanced-cache.php file.
			$adv_cache_file_src = dirname( plugin_dir_path( __FILE__ ) ) . '/advanced-cache.php';

			if ( ! file_exists( $adv_cache_file_src ) ) {
				return;
			}

			$contents = file_get_contents( $adv_cache_file_src );
			$wphb_fs->write( $adv_cache_file_dest, $contents );
		}

		// Try to define WP_CACHE in wp-config.php file.
		$this->check_wp_settings();
	}

	/**
	 * Init page caching preloader.
	 *
	 * @since 2.1.0
	 */
	public function init_preloader() {
		$options = $this->get_options();
		if ( ! isset( $options['preload'] ) || ! $options['preload'] ) {
			return;
		}

		new Preload();
	}

	/**
	 * Make sure advanced-cache.php file is always up to date in between updates.
	 *
	 * @since 2.7.1
	 */
	public function maybe_update_advanced_cache() {
		// No advanced-cache.php, probably cache is disabled - exit.
		if ( ! file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Can't find original, won't be able to replace - exit.
		if ( ! file_exists( dirname( plugin_dir_path( __FILE__ ) ) . '/advanced-cache.php' ) ) {
			return;
		}

		// Files are identical - exit.
		if ( filesize( WP_CONTENT_DIR . '/advanced-cache.php' ) === filesize( dirname( plugin_dir_path( __FILE__ ) ) . '/advanced-cache.php' ) ) {
			return;
		}

		// Check if this is an advanced-cache.php file from Hummingbird.
		$adv_cache_content = file_get_contents( WP_CONTENT_DIR . '/advanced-cache.php' );
		if ( false === strpos( $adv_cache_content, 'WPHB_ADVANCED_CACHE' ) ) {
			// File is not from Hummingbird - exit.
			return;
		}

		unlink( WP_CONTENT_DIR . '/advanced-cache.php' );

		// Create the file.
		$this->init_filesystem();
	}

	/**
	 * *************************
	 * II. HELPER FUNCTIONS
	 * Most of the methods here are private and static because they are internal.
	 *
	 * Available methods:
	 * load_config()
	 * get_settings()
	 * get_default_settings()
	 * check_wp_settings()
	 * get_page_types()
	 * get_file_cache_path()
	 * get_cookies()
	 * skip_url()
	 * skip_user_agent()
	 * skip_page_type()
	 * logged_in_user()
	 * skip_subsite()
	 * can_serve_compressed()
	 * skip_mobile_agent()
	 * skip_custom_cookie()
	 ***************************/

	/**
	 * Get config from file and prepare for use.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::should_cache_request()
	 * @used-by Page_Cache::post_edit()
	 * @used-by Page_Cache::post_status_change()
	 */
	private static function load_config() {
		global $wphb_cache_config;

		self::log_msg( 'Loading config file.' );

		$config_file = WP_CONTENT_DIR . '/wphb-cache/wphb-cache.php';
		if ( ! file_exists( $config_file ) ) {
			self::log_msg( 'Config file does not exist. Loading defaults.' );
			// This is only a fallback so we don't error out. Config file will be written as soon as user logs in.
			$settings = self::get_default_settings();
		} else {
			$settings = json_decode( file_get_contents( $config_file ), true );
		}

		$wphb_cache_config            = new stdClass();
		$wphb_cache_config->cache_dir = WP_CONTENT_DIR . '/wphb-cache/cache/';
		// Cache selected page types.
		$wphb_cache_config->page_types = $settings['page_types'];

		// Clear cache interval.
		$wphb_cache_config->clear_interval = isset( $settings['clear_interval'] ) ? $settings['clear_interval'] : false;

		// Custom post types.
		$wphb_cache_config->custom_post_types = isset( $settings['custom_post_types'] ) ? $settings['custom_post_types'] : array();
		// Cache if user is logged in.
		$wphb_cache_config->cache_logged_in = (bool) $settings['settings']['logged_in'];
		// Cache if the URL has $_GET params or not.
		$wphb_cache_config->cache_with_get_params = (bool) $settings['settings']['url_queries'];
		// Cache 404 pages.
		$wphb_cache_config->cache_404 = (bool) $settings['settings']['cache_404'];
		// Clear cache on update.
		$wphb_cache_config->clear_on_update = (bool) $settings['settings']['clear_update'];
		// Enable debug log.
		$wphb_cache_config->debug_log = (bool) $settings['settings']['debug_log'];
		// Show cache identifier.
		$wphb_cache_config->cache_identifier = isset( $settings['settings']['cache_identifier'] ) ? (bool) $settings['settings']['cache_identifier'] : true;
		// Gzip compression of cached files.
		$wphb_cache_config->compress = isset( $settings['settings']['compress'] ) ? (bool) $settings['settings']['compress'] : false;
		// Cache on mobile devices.
		$wphb_cache_config->mobile = isset( $settings['settings']['mobile'] ) ? (bool) $settings['settings']['mobile'] : true;
		// Clear cache on comment post.
		$wphb_cache_config->comment_clear = isset( $settings['settings']['comment_clear'] ) ? (bool) $settings['settings']['comment_clear'] : true;
		// Cache Headers.
		$wphb_cache_config->cache_headers = isset( $settings['settings']['cache_headers'] ) ? (bool) $settings['settings']['cache_headers'] : false;

		$wphb_cache_config->exclude_url     = isset( $settings['exclude']['url_strings'] ) && is_array( $settings['exclude']['url_strings'] ) ? $settings['exclude']['url_strings'] : array();
		$wphb_cache_config->exclude_agents  = isset( $settings['exclude']['user_agents'] ) && is_array( $settings['exclude']['user_agents'] ) ? $settings['exclude']['user_agents'] : array();
		$wphb_cache_config->exclude_cookies = isset( $settings['exclude']['cookies'] ) && is_array( $settings['exclude']['cookies'] ) ? $settings['exclude']['cookies'] : array();
	}

	/**
	 * Check if the config file is in place and get the settings.
	 *
	 * TODO: refactor this. Now only used to get settings in page caching page. We need to create a file if it doesn't exist for the method above
	 *
	 * @since   1.7.0
	 * @used-by \Hummingbird\Admin\Pages\Caching::page_caching_metabox()
	 */
	public function get_settings() {
		global $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		$config_file = $wphb_fs->basedir . 'wphb-cache.php';

		$settings = $defaults = self::get_default_settings();

		if ( file_exists( $config_file ) ) {
			$settings             = json_decode( file_get_contents( $config_file ), true );
			$settings             = wp_parse_args( $settings, $defaults );
			$settings['settings'] = wp_parse_args( $settings['settings'], $defaults['settings'] );
		} else {
			self::log_msg( 'Config file not found at: ' . $config_file );
			$this->write_file( $config_file, wp_json_encode( $defaults ) );
		}

		return $settings;
	}

	/**
	 * Get array of default settings.
	 *
	 * @since 1.7.2
	 * @since 2.1.0  Changed to public.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'page_types'        => self::get_page_types( true ),
			'custom_post_types' => array(),
			'clear_interval'    => array(
				'enabled'  => false,
				'interval' => 720, // 30 days in hours.
			),
			'settings'          => array(
				'logged_in'        => 0,
				'url_queries'      => 0,
				'cache_404'        => 0,
				'clear_update'     => 0,
				'debug_log'        => 0,
				'cache_identifier' => 1,
				'compress'         => 0,
				'mobile'           => 1,
				'comment_clear'    => 1,
				'cache_headers'    => 0,
			),
			'exclude'           => array(
				'url_strings' => array( 'wp-.*\.php', 'index\.php', 'xmlrpc\.php', 'sitemap[^\/.]*\.xml' ),
				'user_agents' => array( 'bot', 'is_archive', 'slurp', 'crawl', 'spider', 'Yandex' ),
				'cookies'     => array( 'wp_woocommerce_session_' ),
			),
		);
	}

	/**
	 * Check if WP_CACHE is set.
	 *
	 * @since 1.7.0
	 *
	 * @access  private
	 * @used-by Page_Cache::activate()
	 *
	 * @param bool $activate  Skip the WP_CACHE check on activation, will be checked in init_filesystem() method.
	 *
	 * @return bool
	 */
	public function check_wp_settings( $activate = false ) {
		// WP_CACHE is already defined.
		if ( $activate || ( defined( 'WP_CACHE' ) && WP_CACHE ) ) {
			$this->error = false;
			return true;
		}

		// Could not find the file.
		if ( ! file_exists( $this->wp_config_file ) ) {
			$this->error = new WP_Error(
				'no-wp-config-file',
				sprintf( /* translators: %1$s - code tag, %2$s - closing code tag, %3$s - button tag, %4$s - closing button tag */
					__( "Hummingbird could not locate your site’s wp-config.php file. Please ensure the following line has been added to the file:%1\$sdefine('WP_CACHE', true);%2\$sClick Retry to try again.%3\$sRetry%4\$s", 'wphb' ),
					'<br><code>',
					'</code><br><br>',
					'<br><a href="' . esc_url( network_admin_url( 'admin.php?page=wphb-caching' ) ) . '" style="margin-top: 10px" class="sui-button sui-button-blue">',
					'</a>'
				)
			);

			return false;
		}

		// wp-config.php is not writable.
		if ( ! is_writable( $this->wp_config_file ) || ! is_writable( dirname( $this->wp_config_file ) ) ) {
			$this->error = new WP_Error(
				'wp-config-not-writable',
				sprintf( /* translators: %1$s - code tag, %2$s - closing code tag, button tag, %3$s - closing button tag */
					__( "Hummingbird could not write to your site’s wp-config.php file. Click Retry to try again, or manually add the following line to the file:%1\$sdefine('WP_CACHE', true);%2\$sRetry%3\$s", 'wphb' ),
					'<br><code>',
					'</code><br><a href="' . esc_url( network_admin_url( 'admin.php?page=wphb-caching' ) ) . '" style="margin-top: 10px" class="sui-button sui-button-blue">',
					'</a>'
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Return the list of available page types.
	 *
	 * @since   1.7.0
	 * @used-by Page_Cache::get_settings()
	 * @used-by \Hummingbird\Admin\Pages\Caching::page_caching_metabox()
	 *
	 * @param bool $keys  Only array keys or with translations.
	 *
	 * @return array
	 */
	public static function get_page_types( $keys = false ) {
		if ( $keys ) {
			return array( 'frontpage', 'home', 'page', 'single', 'archive', 'category', 'tag' );
		}

		return array(
			'frontpage' => __( 'Frontpage', 'wphb' ),
			'home'      => __( 'Blog', 'wphb' ),
			'page'      => __( 'Pages', 'wphb' ),
			'single'    => __( 'Posts', 'wphb' ),
			'archive'   => __( 'Archives', 'wphb' ),
			'category'  => __( 'Categories', 'wphb' ),
			'tag'       => __( 'Tags', 'wphb' ),
		);
	}
	/**
	 * Skip custom post type added in settings.
	 *
	 * @since   1.9.0
	 * @access  private
	 * @param string $post_type  Post type to check in settings.
	 *
	 * @return bool
	 */
	private static function skip_custom_post_type( $post_type ) {
		global $wphb_cache_config;

		if ( in_array( $post_type, $wphb_cache_config->custom_post_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return file path for the cached file.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::serve_cache()
	 * @used-by Page_Cache::init_caching()
	 * @param   string $request_uri  URI string.
	 */
	private static function get_file_cache_path( $request_uri ) {
		global $wphb_cache_config, $wphb_cache_file, $wphb_meta_file;

		// Prepare some variables.
		$http_host = htmlentities( stripslashes( $_SERVER['HTTP_HOST'] ) ); // Input var ok.
		$port      = isset( $_SERVER['SERVER_PORT'] ) ? (int) $_SERVER['SERVER_PORT'] : 0; // Input var ok.

		/**
		 * Generate cache hash.
		 */
		// Remove index.php from query.
		$hash = str_replace( '/index.php', '/', $request_uri );
		// Remove any query hash from request URI.
		$hash    = preg_replace( '/#.*$/', '', $hash );
		$cookies = self::get_cookies();
		$hash    = md5( $http_host . $hash . $port . $cookies );

		// Remove get params.
		$request_uri = preg_replace( '/(\?.*)?$/', '', $request_uri );

		$ext = '.html';
		if ( $wphb_cache_config->cache_logged_in ) {
			/**
			 * If caching for logged-in users, we need to set the cache file extension to .php and
			 * add die(); in file header, to prevent phishing attacks.
			 */
			$ext = '.php';
		}

		$mobile = self::is_mobile_agent() ? '/mobile/' : '';

		$filename = str_replace( '//', '/', $wphb_cache_config->cache_dir . $http_host . $mobile . $request_uri . $hash );

		$wphb_cache_file = $filename . $ext;
		$wphb_meta_file  = $filename . '-meta.php';

		self::log_msg( 'Caching to file: ' . $wphb_cache_file );
	}

	/**
	 * Get cookie keys for generating file hash.
	 *
	 * @since   1.7.0
	 * @used-by Page_Cache::prepare_file()
	 *
	 * @return string
	 */
	private static function get_cookies() {
		static $cookie_value = '';

		if ( ! empty( $cookie_value ) ) {
			self::log_msg( 'Cookie cached: ' . $cookie_value );
			return $cookie_value;
		}

		foreach ( (array) $_COOKIE as $key => $value ) { // Input var ok.
			// Check password protected post, comment author, logged in user.
			if ( preg_match( '/^wp-postpass_|^comment_author_|^wordpress_logged_in_|^wphb_cache_/', $key ) ) {
				self::log_msg( 'Found cookie: ' . $key );
				$cookie_value .= $_COOKIE[ $key ] . ','; // Input var ok.
			}
		}

		if ( ! empty( $cookie_value ) ) {
			$cookie_value = md5( $cookie_value );
			self::log_msg( 'Cookie hashed value: ' . $cookie_value );
		}

		return $cookie_value;
	}

	/**
	 * Check if the URL is in the exception list in the settings.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::should_cache_request()
	 * @param   string $uri  URL to skip.
	 *
	 * @return bool
	 */
	private static function skip_url( $uri ) {
		global $wphb_cache_config;

		// Remove empty values.
		$uri_pattern = array_filter( $wphb_cache_config->exclude_url );
		if ( ! is_array( $uri_pattern ) || empty( $uri_pattern ) ) {
			return false;
		}

		$uri_pattern = implode( '|', $wphb_cache_config->exclude_url );
		if ( preg_match( "/{$uri_pattern}/i", $uri ) ) {
			return true;
		}

		// Now do the same, but test the URI as part of the full URL.
		$http_host = isset( $_SERVER['HTTP_HOST'] ) ? htmlentities( stripslashes( $_SERVER['HTTP_HOST'] ) ) : '';
		$http_prot = isset( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] ? 'https://' : 'http://';
		if ( preg_match( "/{$uri_pattern}/i", $http_prot . $http_host . $uri ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the user agent is in the exception list in the settings.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::should_cache_request()
	 *
	 * @return bool
	 */
	private static function skip_user_agent() {
		global $wphb_cache_config;

		// Remove empty values.
		$agent_pattern = array_filter( $wphb_cache_config->exclude_agents );
		if ( ! is_array( $agent_pattern ) || empty( $agent_pattern ) ) {
			return false;
		}

		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? stripslashes( $_SERVER['HTTP_USER_AGENT'] ) : ''; // Input var ok.

		// In case no user agent or agent is in exclude list, we do not cache the page.
		if ( empty( $agent ) || in_array( $agent, $agent_pattern, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Skip page type selected in settings.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::cache_request()
	 *
	 * @return bool
	 */
	private static function skip_page_type() {
		global $wphb_cache_config;

		if ( ! is_array( $wphb_cache_config->page_types ) ) {
			return false;
		}
		$blog_is_frontpage = 'posts' === get_option( 'show_on_front' ) && ! is_multisite();

		if ( is_front_page() && ! in_array( 'frontpage', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( is_home() && ! in_array( 'home', $wphb_cache_config->page_types, true ) && ! $blog_is_frontpage ) {
			return true;
		} elseif ( is_page() && ! in_array( 'page', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( is_single() && ! in_array( 'single', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( is_archive() && ! in_array( 'archive', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( is_category() && ! in_array( 'category', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( is_tag() && ! in_array( 'tag', $wphb_cache_config->page_types, true ) ) {
			return true;
		} elseif ( self::skip_custom_post_type( get_post_type() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the user is logged in.
	 *
	 * @since 1.7.0
	 * @access private
	 * @used-by Page_Cache::should_cache_request()
	 *
	 * @return bool
	 */
	private static function logged_in_user() {
		if ( function_exists( 'is_user_logged_in' ) ) {
			return is_user_logged_in();
		}

		foreach ( (array) $_COOKIE as $key => $value ) { // Input var ok.
			// Check logged in user.
			if ( preg_match( '/^wordpress_logged_in_/', $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Skip subsite when administrator has turned off page caching.
	 *
	 * @since   1.8.0
	 * @access  private
	 * @used-by Page_Cache::cache_request()
	 *
	 * @return bool
	 */
	private static function skip_subsite() {
		if ( ! is_multisite() ) {
			return false;
		}

		$options = Utils::get_module( 'page_cache' )->get_options();

		return ! $options['enabled'];
	}

	/**
	 * Check if we should be compressing.
	 *
	 * @since 2.1
	 *
	 * @return bool|string
	 */
	private static function can_serve_compressed() {
		if ( 1 === ini_get( 'zlib.output_compression' ) || 'on' === strtolower( ini_get( 'zlib.output_compression' ) ) ) {
			return false;
		}

		if ( ! isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) || false === strpos( stripslashes( $_SERVER['HTTP_ACCEPT_ENCODING'] ), 'gzip' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if mobile user agent.
	 *
	 * @since 2.2.0
	 * @access private
	 * @used-by Page_Cache::should_cache_request()
	 * @return bool
	 */
	private static function is_mobile_agent() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( stripslashes( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // Input var ok.

		$result = array_filter(
			self::$mobile_agents,
			function( $browser_agent ) use ( $user_agent ) {
				return false !== strstr( $user_agent, trim( strtolower( $browser_agent ) ) );
			}
		);

		return ! empty( $result );
	}

	/**
	 * Skip pages with predefined cookies in page caching settings.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	private static function skip_custom_cookies() {
		global $wphb_cache_config;

		// Remove empty values.
		$cookies = array_filter( $wphb_cache_config->exclude_cookies );
		if ( ! is_array( $cookies ) || empty( $cookies ) ) {
			return false;
		}

		$uri_pattern = implode( '|', $wphb_cache_config->exclude_cookies );

		foreach ( (array) $_COOKIE as $key => $value ) { // Input var ok.
			if ( preg_match( "/{$uri_pattern}/i", $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * *************************
	 * III. FILESYSTEM FUNCTIONS
	 *
	 * Available methods:
	 * write_file()
	 * add_index()
	 * save_settings()
	 * disable()
	 ***************************/

	/**
	 * Write page buffer to file.
	 *
	 * @since   1.7.0
	 * @used-by Page_Cache::get_settings()
	 * @used-by Page_Cache::cache_request()
	 * @param   string $file     File name.
	 * @param   string $content  File content.
	 */
	private function write_file( $file, $content ) {
		global $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		$wphb_fs->write( $file, $content );
		$this->add_index( dirname( $file ) );
	}

	/**
	 * Add empty index.html file for protection.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @param   string $dir  Directory path.
	 * @used-by Page_Cache::write_file()
	 */
	private function add_index( $dir ) {
		if ( is_dir( $dir ) && is_file( "{$dir}/index.html" ) ) {
			return;
		}

		$file = fopen( "{$dir}/index.html", 'w' );
		if ( $file ) {
			fclose( $file );
		}
	}

	/**
	 * Save settings to file.
	 *
	 * @since   1.7.0
	 * @param   array $settings  Settings array.
	 * @used-by \Hummingbird\Admin\Pages\Caching::on_load()
	 */
	public function save_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return;
		}

		// If non member enable cache_identifier.
		if ( ! Utils::is_member() ) {
			$settings['settings']['cache_identifier'] = 1;
		}

		global $wphb_cache_config, $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		$wphb_cache_config            = new stdClass();
		$wphb_cache_config->cache_dir = $wphb_fs->cache_dir;

		$config_file = $wphb_fs->basedir . 'wphb-cache.php';

		self::log_msg( 'Writing configuration to: ' . $config_file );
		$this->write_file( $config_file, wp_json_encode( $settings ) );

		$this->clear_cache();
	}

	/**
	 * Disable page caching:
	 * - removes advanced-cache.php file
	 * - removes WP_CACHE from wp-config.php
	 * - purge cache folder
	 *
	 * @since   1.7.0
	 * @since   1.8.0  Changed access to private.
	 * @access  private
	 * @used-by Page_Cache::toggle_service()
	 */
	private function cleanup() {
		global $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		if ( $wphb_fs->purge() ) {
			self::log_msg( 'Page cache deactivation: successfully purged cache folder.' );
		} else {
			self::log_msg( 'Page cache deactivation: error purging cache folder.' );
		}

		// Do not disable page caching completely on MU if disabling only for subsite.
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		// Remove advanced-cache.php.
		$adv_cache_file = dirname( get_theme_root() ) . '/advanced-cache.php';

		// If no file or file not writable - exit.
		if ( ! file_exists( $adv_cache_file ) || ! is_writable( $adv_cache_file ) ) {
			self::log_msg( 'Page cache deactivation: unable to remove advanced-cache.php.' );
			return;
		}

		// Remove only Hummingbird file.
		if ( false !== strpos( file_get_contents( $adv_cache_file ), 'WPHB_ADVANCED_CACHE' ) ) {
			$msg = 'Page cache deactivation: error removing advanced-cache.php file.';
			if ( unlink( $adv_cache_file ) ) {
				self::log_msg( 'Page cache deactivation: advanced-cache.php file removed.' );
			}

			self::log_msg( $msg );
		}

		// Reset cached pages count to 0.
		Settings::update_setting( 'pages_cached', 0, 'page_cache' );
	}

	/**
	 * *************************
	 * IV. CACHE CONTROL FUNCTIONS
	 *
	 * Available methods:
	 * should_cache_request()
	 * cache_request()
	 * send_headers()
	 * clear_cache()
	 * purge_post_cache()
	 * clear_external_cache()
	 * cache_home_page()
	 ***************************/

	/**
	 * Should we cache the request or not.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::serve_cache()
	 * @used-by Page_Cache::init_caching()
	 * @param   string $request_uri  Request URI.
	 *
	 * @return bool
	 */
	private static function should_cache_request( $request_uri ) {
		global $wphb_cache_config;

		// In most cases the filter is used to disable caching on incompatible hosts.
		$state = apply_filters( 'wphb_shold_cache_request_pre', true );

		if ( ! $state ) {
			self::log_msg( apply_filters( 'wphb_should_cache_request_pre', 'Do not cache, blocked by filter' ) );
			return false;
		}

		self::load_config();

		if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			self::log_msg( 'Page not cached because of active cron or ajax request.' );
			return false;
		} elseif ( is_admin() ) {
			self::log_msg( 'Do not cache admin pages.' );
			return false;
		} elseif ( self::logged_in_user() && ! $wphb_cache_config->cache_logged_in ) {
			self::log_msg( 'Do not cache pages for logged in users.' );
			return false;
		} elseif ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] ) { // Input var okay.
			self::log_msg( "Skipping page. Used {$_SERVER['REQUEST_METHOD']} method. Only GET allowed." ); // Input var ok.
			return false;
		} elseif ( isset( $_GET['preview'] ) ) { // Input var okay.
			self::log_msg( 'Do not cache preview post pages.' );
			return false;
		} elseif ( false === empty( $_GET ) && ! $wphb_cache_config->cache_with_get_params ) { // Input var ok.
			self::log_msg( 'Skipping page with GET params.' );
			return false;
		} elseif ( preg_match( '/^\/wp.*php$/', strtok( $request_uri, '?' ) ) ) {
			// Remove string parameters and do not cache any /wp-login.php or /wp-admin/*.php pages.
			// TODO: Maybe improve regex, as it takes a bit more than needed.
			self::log_msg( 'Do not cache wp-admin pages.' );
			return false;
		} elseif ( self::skip_url( $request_uri ) ) {
			self::log_msg( 'Do not cache page. URL exclusion rule match: ' . $request_uri );
			return false;
		} elseif ( self::skip_user_agent() ) {
			self::log_msg( 'Do not cache page. User-Agent is empty or excluded in settings.' );
			return false;
		} elseif ( ! $wphb_cache_config->mobile && self::is_mobile_agent() ) {
			self::log_msg( 'Do not cache page. Mobile agents are excluded in settings.' );
			return false;
		} elseif ( self::skip_custom_cookies() ) {
			self::log_msg( 'Do not cache page. Found excluded cookie.' );
			return false;
		} elseif ( ! isset( $_SERVER['HTTP_HOST'] ) ) { // Input var ok.
			self::log_msg( 'Page can not be cached, no HTTP_HOST set.' );
			return false;
		}

		self::log_msg( 'Request passed should_cache_request check. Ready to cache.' );

		return true;
	}

	/**
	 * Parse the buffer. Used in callback for ob_start in init_caching().
	 *
	 * @since   1.7.0
	 * @used-by Page_Cache::init_caching()
	 * @param   string $buffer  Page buffer.
	 *
	 * @return mixed
	 */
	public function cache_request( $buffer ) {
		global $wphb_cache_file, $wphb_cache_config, $wphb_meta_file;

		// We need this to be able to counter generating pages right after clearing AO settings, queue initiated on page load.
		if ( get_transient( 'wphb-processing' ) ) {
			// Exit early.
			self::log_msg( 'Page not cached. Asset optimization processing in progress. Sending buffer to user.' );
			return $buffer;
		}

		$cache_page = true;
		$is_404     = false;

		if ( empty( $buffer ) ) {
			$cache_page = false;
			self::log_msg( 'Empty buffer. Exiting.' );
		}

		$http_response_code = http_response_code();
		if ( ! in_array( $http_response_code, array( 200, 404 ), true ) ) {
			$cache_page = false;
			self::log_msg( 'Page not cached because unsupported http response code: ' . $http_response_code );
		}

		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			$cache_page = false;
			self::log_msg( 'Page not cached because DONOTCACHEPAGE is defined.' );
		} elseif ( is_feed() ) {
			$cache_page = false;
			self::log_msg( 'Do not cache feeds.' );
		} elseif ( self::skip_page_type() ) {
			$cache_page = false;
			self::log_msg( 'Do not cache page. Skipped in settings.' );
		} elseif ( ! preg_match( '/(<\/html>|<\/rss>|<\/feed>|<\/urlset|<\?xml)/i', $buffer ) ) {
			$cache_page = false;
			self::log_msg( 'HTML corrupt. Page not cached.' );
		} elseif ( self::skip_subsite() ) {
			$cache_page = false;
			self::log_msg( 'Do not cache page. Subsite caching disabled in settings.' );
		}

		// Handle 404 pages.
		if ( is_404() ) {
			if ( ! $wphb_cache_config->cache_404 ) {
				$cache_page = false;
				self::log_msg( 'Do not cache 404 pages.' );
			} else {
				$is_404 = true;
				self::log_msg( '404 page found. Caching for 404 enabled. Page will be cached.' );
			}
		}

		if ( ! $cache_page ) {
			self::log_msg( 'Page not cached. Sending buffer to user.' );
			return $buffer;
		}

		$content = '';
		if ( $wphb_cache_config->cache_identifier ) {
			$content = '<!-- This page is cached by the Hummingbird Performance plugin v' . WPHB_VERSION . ' - https://wordpress.org/plugins/hummingbird-performance/. -->';
		}
		$content       .= $buffer;
		$time_to_create = microtime( true ) - $this->start_time;

		if ( $wphb_cache_config->cache_identifier ) {
			$content .= '<!-- Hummingbird cache file was created in ' . $time_to_create . ' seconds, on ' . gmdate( 'd-m-y G:i:s', time() ) . ' -->';
		}

		$content = apply_filters( 'wphb_cache_content', $content );

		if ( $wphb_cache_file ) {
			// If this is php file and caching for logged-in users - add die() on top (except for when it's compressed, just to avoid that extra decode step).
			if ( preg_match( '/\.php/', basename( $wphb_cache_file ) ) && ! $is_404 && ( ! isset( $wphb_cache_config->compress ) || ! $wphb_cache_config->compress ) ) {
				$content = '<?php die(); ?>' . $content;
			}

			// Maybe compress the content?
			if ( $wphb_cache_config->compress && self::can_serve_compressed() ) {
				self::log_msg( 'Applying gzip compression to cached file' );
				$content = gzencode( $content, 7 );
			}

			self::log_msg( 'Saving page to cache file: ' . $wphb_cache_file );
			$this->write_file( $wphb_cache_file, $content );

			// Cache Headers if enabled.
			if ( $wphb_cache_config->cache_headers ) {
				self::log_msg( 'Saving page headers to cache file: ' . $wphb_meta_file );
				$this->write_file( $wphb_meta_file, '<?php die(); ?>' . wp_json_encode( $this->get_page_headers() ) );
			}

			// Update cached pages count.
			$count = Settings::get_setting( 'pages_cached', 'page_cache' );
			Settings::update_setting( 'pages_cached', ++$count, 'page_cache' );
		}

		return $buffer;
	}

	/**
	 * Send headers to the browser.
	 *
	 * @since   1.7.0
	 * @access  private
	 * @used-by Page_Cache::init_caching()
	 * @used-by Page_Cache::start_cache()
	 */
	private static function send_headers() {
		global $wphb_cache_file, $wphb_cache_config;

		// Get Cached headers.
		$headers = self::get_page_headers_cached();

		$headers_default = array(
			'Content-Type'  => 'Content-Type: text/html; charset=UTF-8',
			'Cache-Control' => 'Cache-Control: max-age=3600, must-revalidate',
		);

		$headers = array_merge( $headers_default, $headers );

		// Get meta from meta file. Meta should contain headers.
		$meta = array(
			'headers' => $headers,
			'uri'     => 'local.wordpress.dev/?switched_off=true',
			'blog_id' => 1,
			'post'    => 0,
			'hash'    => 'local.wordpress.dev80/?switched_off=true',
		);

		// Check last modified time or file.
		$file_modified = filemtime( $wphb_cache_file );
		if ( isset( $file_modified ) ) {
			$meta['headers']['Last-Modified'] = 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $file_modified ) . ' GMT';
		} else {
			$meta['headers']['Last-Modified'] = 'HTTP/1.0 304 Not Modified';
		}

		if ( $wphb_cache_config->compress && self::can_serve_compressed() ) {
			$meta['headers']['Content-Encoding'] = 'Content-Encoding: gzip';
			$meta['headers']['Content-Length']   = 'Content-Length: ' . filesize( $wphb_cache_file );
		}

		foreach ( $meta['headers'] as $t => $header ) {
			/*
			 * Godaddy fix, via http://blog.gneu.org/2008/05/wp-supercache-on-godaddy/ and
			 * http://www.littleredrails.com/blog/2007/09/08/using-wp-cache-on-godaddy-500-error/.
			 */
			if ( strpos( $header, 'Last-Modified:' ) === false ) {
				header( $header );
			}
		}

		header( 'Hummingbird-Cache: Served' );
	}

	/**
	 * Server cached file to user.
	 *
	 * @since   1.7.2
	 * @access  private
	 * @used-by Page_Cache::init_caching()
	 * @used-by Page_Cache::start_cache()
	 * @param   string $wphb_cache_file  File to cache.
	 */
	private static function send_file( $wphb_cache_file ) {
		// If this is php file (caching for logged-in users - remove die().
		if ( preg_match( '/\.php/', basename( $wphb_cache_file ) ) ) {
			$content = file_get_contents( $wphb_cache_file );
			/* Remove <?php die(); ?> from file */
			if ( 0 === strpos( $content, '<?php die(); ?>' ) ) {
				$content = substr( $content, 15 );
			}
			echo $content;
			exit();
		}

		if ( defined( 'WPMU_ACCEL_REDIRECT' ) && WPMU_ACCEL_REDIRECT ) {
			header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '/wp-content/', $wphb_cache_file ) );
			exit;
		} elseif ( defined( 'WPMU_SENDFILE' ) && WPMU_SENDFILE ) {
			header( 'X-Sendfile: ' . $wphb_cache_file );
			exit;
		} else {
			@readfile( $wphb_cache_file );
			exit();
		}
	}

	/**
	 * Implement abstract parent method for clearing cache.
	 *
	 * Purge cache directory.
	 *
	 * @since   1.7.0
	 * @since   1.7.1 Renamed to clear_cache from purge_cache_dir
	 *
	 * @used-by \Hummingbird\Admin\Pages\Caching::run_actions()
	 * @used-by Page_Cache::save_settings()
	 * @used-by Page_Cache::purge_post_cache()
	 * @used-by Page_Cache::post_edit()
	 * @used-by Page_Cache::post_status_change()
	 *
	 * @param string $directory  Directory to remove.
	 * @param bool   $single     Make sure we only clear out a single directory for posts that are set as a site homepage.
	 *
	 * @return bool
	 */
	public function clear_cache( $directory = '', $single = false ) {
		global $wphb_fs;

		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		$skip_subdirs = true;

		$directory_origin = $directory;

		// Remove notice for clearing page cache.
		delete_option( 'wphb-notice-cache-cleaned-show' );

		/**
		 * Function is_network_admin() does not work in ajax, so this is a hack.
		 *
		 * @see https://core.trac.wordpress.org/ticket/22589
		 */
		$is_network_admin = false;
		if ( is_multisite() && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$is_network_admin = preg_match( '#^' . network_admin_url() . '#i', $_SERVER['HTTP_REFERER'] );
		}

		// For multisite we need to set this to null.
		if ( is_multisite() && ! $is_network_admin && ! $directory ) {
			$current_blog = get_site( get_current_blog_id() );
			$directory    = $current_blog->path;
			$skip_subdirs = false; // We are clearing all cache.
		}

		// Purge whole cache directory.
		if ( ! $directory ) {
			// Reset cached pages count.
			Settings::update_setting( 'pages_cached', 0, 'page_cache' );

			self::log_msg( 'Cache directory purged' );
			$status = $wphb_fs->purge();

			$options = $this->get_options();

			if ( isset( $options['preload'] ) && $options['preload'] && isset( $options['preload_type'] ) && isset( $options['preload_type']['home_page'] ) && $options['preload_type']['home_page'] ) {
				$preload = new Preload();
				$preload->preload_home_page();
			}

			do_action( 'wphb_cache_directory_cleared' );

			// Clear integrations cache.
			do_action( 'wphb_clear_cache_url' );

			return $status;
		}

		// Purge specific folder.
		$http_host = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_host = htmlentities( wp_unslash( $_SERVER['HTTP_HOST'] ) ); // Input var ok.
		} elseif ( function_exists( 'get_option' ) ) {
			$http_host = preg_replace( '/https?:\/\//', '', get_option( 'siteurl' ) );
		}

		/**
		 * Filter the HTTP_HOST value.
		 *
		 * @param string $http_host  Current HTTP host value.
		 *
		 * @since 2.7.3
		 */
		$http_host = apply_filters( 'wphb_page_cache_http_host', $http_host );


		$cache_dir = $http_host . $directory;
		$full_path = $wphb_fs->cache_dir . $cache_dir;

		// Check if current blog is mapped and change directory to mapped domain.
		if ( class_exists( 'domain_map' ) ) {
			global $dm_map;
			$utils         = $dm_map->utils();
			$mapped_domain = $utils->get_mapped_domain();
			if ( $mapped_domain ) {
				$cache_dir = $mapped_domain;
				$full_path = $wphb_fs->cache_dir . $cache_dir;
			}
		}

		// If dir does not exist - return.
		if ( empty( $full_path ) || ! is_dir( $full_path ) ) {
			return true;
		}

		// Decrease cached pages count by 1.
		$count = Settings::get_setting( 'pages_cached', 'page_cache' );

		if ( $wphb_fs->purge( 'cache/' . $http_host . '/mobile' . $directory, false, $skip_subdirs ) ) {
			self::log_msg( 'Mobile cache has been cleared.' );
			Settings::update_setting( 'pages_cached', --$count, 'page_cache' );
		}

		$status = $wphb_fs->purge( 'cache/' . $cache_dir, false, $skip_subdirs );
		if ( $status ) {
			Settings::update_setting( 'pages_cached', --$count, 'page_cache' );
			// Clear integrations cache.
			do_action( 'wphb_clear_cache_url', $directory_origin );
		}

		do_action( 'wphb_page_cache_cleared', $directory_origin );

		return $status;
	}

	/**
	 * Purge single post page cache and relative pages (tags, category and author pages).
	 *
	 * @since   1.7.0
	 * @param   int $post_id  Post ID.
	 * @used-by Page_Cache::post_status_change()
	 * @used-by Page_Cache::post_edit()
	 */
	private function purge_post_cache( $post_id ) {
		global $post_trashed, $wphb_cache_config;

		$replacement = preg_replace( '|https?://[^/]+|i', '', get_option( 'home' ) );
		$permalink   = trailingslashit( str_replace( get_option( 'home' ), $replacement, get_permalink( $post_id ) ) );

		// If post is being trashed.
		if ( $post_trashed ) {
			$permalink = preg_replace( '/__trashed(-?)(\d*)\/$/', '/', $permalink );
		}

		// When we have a static page as a home directory, we need to make sure that we do not clear all the other subfolders.
		$force_single_clear = '/' === $permalink;

		$this->clear_cache( $permalink, $force_single_clear );
		do_action( 'wphb_cloudflare_apo_clear_cache', $post_id );
		self::log_msg( 'Cache has been purged for post id: ' . $post_id );
		do_action( 'wphb_page_cache_preload_page', $permalink );

		// Clear categories and tags pages if cached.
		$meta_array = array(
			'category' => 'category',
			'tag'      => 'post_tag',
		);
		foreach ( $meta_array as $meta_name => $meta_key ) {
			// If not cached, skip meta.
			if ( ! in_array( $meta_name, $wphb_cache_config->page_types, true ) ) {
				continue;
			}

			$metas = get_the_terms( $post_id, $meta_key );

			if ( ! $metas ) {
				continue;
			}

			/**
			 * WP term meta.
			 *
			 * @var WP_Term $meta
			 */
			foreach ( $metas as $meta ) {
				$meta_link = trailingslashit( str_replace( get_option( 'home' ), $replacement, get_category_link( $meta->term_id ) ) );
				$this->clear_cache( $meta_link );
				self::log_msg( "Cache has been purged for {$meta_name}: {$meta->name}" );
			}
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return;
		}

		// Author page.
		if ( isset( $post->post_author ) && 0 !== $post->post_author ) {
			$author_link = trailingslashit( str_replace( get_option( 'home' ), $replacement, get_author_posts_url( $post->post_author ) ) );
			if ( $author_link ) {
				$this->clear_cache( $author_link );
				self::log_msg( "Cache has been purged for author page: $author_link" );
			}
		}

		/**
		 * Support for custom terms.
		 *
		 * @since 2.0.0
		 */
		$custom_terms = apply_filters( 'wphb_page_cache_custom_terms', array() );
		foreach ( $custom_terms as $term ) {
			$metas = get_the_terms( $post_id, $term );

			if ( ! $metas && ! is_wp_error( $metas ) ) {
				continue;
			}

			foreach ( $metas as $meta ) {
				if ( ! isset( $meta->term_id ) && ! is_wp_error( $meta ) ) {
					continue;
				}

				$meta_link = str_replace( get_option( 'home' ), $replacement, get_term_link( $meta->term_id, $term ) );
				$this->clear_cache( $meta_link );
				self::log_msg( "Cache has been purged for {$term}: {$meta->name}" );

				if ( ( ! isset( $meta->parent ) || 0 === $meta->parent ) && ! is_wp_error( $meta ) ) {
					continue;
				}

				$meta_link = str_replace( get_option( 'home' ), $replacement, get_term_link( $meta->parent, $term ) );
				$this->clear_cache( $meta_link );
				self::log_msg( "Cache has been purged for {$term}: {$meta->name}" );
			}
		}
	}

	/**
	 * Purge cache for specific page in external cache service (varnish).
	 *
	 * @since 2.1.0
	 * @param string $path  Path for which to clear cache.
	 */
	public function clear_external_cache( $path ) {
		$options = $this->get_options();

		if ( isset( $options['integrations']['varnish'] ) && $options['integrations']['varnish'] ) {
			Utils::get_api()->varnish->purge_cache( $path );
		}
	}

	/**
	 * *************************
	 * V. ACTIONS AND FILTERS
	 *
	 * Available methods:
	 * serve_cache()
	 * init_caching()
	 * post_status_change()
	 * post_edit()
	 * log()
	 * clear_cache_button()
	 * clear_cache_message()
	 * clear_cache_action()
	 * toggle_service()
	 * clear_on_comment_post()
	 * module_status()
	 ***************************/

	/**
	 * Server a cached file.
	 *
	 * @since 1.7.0
	 * @used-by advanced-cache.php
	 */
	public static function serve_cache() {
		global $wphb_cache_file, $wphb_cache_config, $wphb_meta_file;

		// Exit early if in admin.
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? stripslashes( $_SERVER['REQUEST_URI'] ) : ''; // Input var ok.

		if ( ! self::should_cache_request( $request_uri ) ) {
			return;
		}

		/**
		 * 1. Get the file and header names.
		 * $wphb_cache_file available with path to cached file
		 * Generate file path where the cache will be saved.
		 */
		self::get_file_cache_path( $request_uri );

		/**
		 * 2. Check if the files are there?
		 */
		if ( file_exists( $wphb_cache_file ) ) {
			// Check expiry.
			if ( isset( $wphb_cache_config->clear_interval['enabled'] ) && $wphb_cache_config->clear_interval['enabled'] ) {
				self::log_msg(
					sprintf(
						'Cache file found. Expiry set to %s hours, file is %s hours old.',
						$wphb_cache_config->clear_interval['interval'],
						round( ( time() - filemtime( $wphb_cache_file ) ) / HOUR_IN_SECONDS )
					)
				);

				if ( time() - filemtime( $wphb_cache_file ) >= $wphb_cache_config->clear_interval['interval'] * HOUR_IN_SECONDS ) {
					self::log_msg( 'Cache file found, but cache interval is defined and cache is expired. Removing file.' );
					unlink( $wphb_cache_file );
					if ( file_exists( $wphb_meta_file ) ) {
						unlink( $wphb_meta_file );
					}
					return;
				}
			}
			// Cache headers enabled: Check for header cache file, if doesn't exists; unlink page cache file.
			if ( $wphb_cache_config->cache_headers && ! file_exists( $wphb_meta_file ) ) {
				self::log_msg( "Cache file found, but header cache file doesn't exists. Removing file." );
				unlink( $wphb_cache_file );
				return;
			}

			self::log_msg( 'Cached file found. Serving to user.' );

			self::send_headers();

			self::send_file( $wphb_cache_file );
		}
	}

	/**
	 * Try to avoid WP functions here (though we need to test).
	 *
	 * @since   1.7.0
	 * @used-by init action
	 */
	public function init_caching() {
		global $wphb_cache_file, $wphb_cache_config, $wphb_meta_file;

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? stripslashes( $_SERVER['REQUEST_URI'] ) : ''; // Input var ok.

		if ( ! self::should_cache_request( $request_uri ) ) {
			return;
		}

		/**
		 * 1. Get the file and header names.
		 * $wphb_cache_file available with path to cached file
		 * Generate file path where the cache will be saved.
		 */
		self::get_file_cache_path( $request_uri );

		$is_cached = file_exists( $wphb_cache_file );

		if ( $is_cached && $wphb_cache_config->cache_headers ) {
			$is_cached = file_exists( $wphb_meta_file );
		}

		/**
		 * 2. Check if the files are there?
		 */
		if ( $is_cached ) {
			self::log_msg( 'Cached file found. Serving to user.' );

			self::send_headers();

			self::send_file( $wphb_cache_file );
		} else {
			self::log_msg( 'Cached file not found. Passing to ob_start.' );
			// Write the file and send headers.
			$this->start_time = microtime( true );
			ob_start( array( $this, 'cache_request' ) );
		}
	}

	/**
	 * Parse post status transitions.
	 *
	 * @since   1.7.0
	 * @param   string  $new_status  New post status.
	 * @param   string  $old_status  Old post status.
	 * @param   WP_Post $post        Post object.
	 * @used-by transition_post_status action
	 */
	public function post_status_change( $new_status, $old_status, $post ) {
		global $post_trashed, $wphb_cache_config;

		// Only trigger for public post types.
		if ( ! isset( $post->post_type ) || ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}

		// Nothing changed or revision. Exit.
		if ( $new_status === $old_status || wp_is_post_revision( $post->ID ) ) {
			return;
		}

		// New post in draft mode. Exit.
		if ( 'auto-draft' === $new_status || 'draft' === $new_status ) {
			return;
		}

		$post_trashed = false;
		if ( 'trash' === $new_status ) {
			$post_trashed = true;
		}

		// Purge cache on post publish/un-publish/move to trash.
		if ( ( 'publish' === $new_status && 'publish' !== $old_status ) || 'trash' === $new_status ) {
			// If settings not loaded - load them.
			if ( ! isset( $wphb_cache_config ) ) {
				self::load_config();
			}

			// Clear all cache files and return.
			if ( $wphb_cache_config->clear_on_update ) {
				$this->clear_cache();
				return;
			}

			// Delete category and tag cache.
			// Delete page cache.
			$this->purge_post_cache( $post->ID );
		}
	}

	/**
	 * Fires on edit_post action.
	 *
	 * @since   1.7.0
	 * @param   int $post_id  Post ID.
	 * @used-by edit_post action
	 */
	public function post_edit( $post_id ) {
		global $wphb_cache_config;

		// Clear cache button on post edit pressed.
		if ( isset( $_POST['wphb-clear-cache'] ) ) {
			// Delete page cache.
			$this->purge_post_cache( $post_id );

			// This variable doesn't really do anything... If you comment it out, nothing will change, except the
			// same variable below in apply_filters will be underlined in all code editors.
			$messages['post'][4] = __( 'Cache for post has been cleared.', 'wphb' );
			apply_filters( 'post_updated_messages', $messages );

			return;
		}

		// The is_nav_menu_item() check will prevent cache clear on Appearance - Menus save action.
		if ( wp_is_post_revision( $post_id ) || is_nav_menu_item( $post_id ) ) {
			return;
		}

		// If settings not loaded - load them.
		if ( ! isset( $wphb_cache_config ) ) {
			self::load_config();
		}

		// Clear all cache files and return.
		if ( $wphb_cache_config->clear_on_update ) {
			$this->clear_cache();
		} else {
			// Delete category and tag cache.
			// Delete page cache.
			$this->purge_post_cache( $post_id );
		}
	}

	/**
	 * Write notice or error to debug.log
	 *
	 * @since 1.7.0
	 * @param mixed $message  Error/notice message.
	 */
	public static function log_msg( $message ) {
		// Check that page caching logging is enabled.
		$config_file = WP_CONTENT_DIR . '/wphb-cache/wphb-cache.php';
		if ( ! file_exists( $config_file ) ) {
			return;
		}
		$settings = json_decode( file_get_contents( $config_file ), true );

		if ( ! (bool) $settings['settings']['debug_log'] ) {
			return;
		}

		if ( ! is_string( $message ) || is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		$message = '[' . date( 'c' ) . '] ' . $message . PHP_EOL;

		$file = WP_CONTENT_DIR . '/wphb-logs/page-caching-log.php';

		// If file does not exist, we need to create it and add the die() header.
		if ( ! file_exists( $file ) ) {
			global $wphb_fs;

			if ( ! $wphb_fs && class_exists( 'Filesystem' ) ) {
				$wphb_fs = Filesystem::instance();
			}

			if ( $wphb_fs ) {
				$wphb_fs->write( $file, '<?php die(); ?>' . PHP_EOL );
			}
		}

		error_log( $message, 3, $file );
	}

	/**
	 * Add a clear cache button to edit post screen (under published on field).
	 *
	 * @since 1.8
	 *
	 * @param WP_Post $post  Post object.
	 * @used-by Page_Cache::run() (post_submitbox_misc_actions action).
	 */
	public function clear_cache_button( $post ) {
		?>
		<div class="misc-pub-section wphb-clear-cache-button">
			<input type="submit" value="<?php esc_attr_e( 'Clear cache', 'wphb' ); ?>" class="button" id="wphb-clear-cache" name="wphb-clear-cache">
		</div>
		<?php
	}

	/**
	 * Overwrite message when the clear cache button has been pressed.
	 *
	 * @since 1.8
	 *
	 * @param array $messages  Messages.
	 * @used-by Page_Cache::run() (post_updated_messages filter)
	 *
	 * @return mixed
	 */
	public function clear_cache_message( $messages ) {
		$messages['post'][4] = __( 'Cache for post has been cleared.', 'wphb' );
		return $messages;
	}

	/**
	 * Trigger a cache clear.
	 *
	 * If post ID is set, will try to clear cache for that page or post with all the related
	 * taxonomies (tags, category and author pages).
	 *
	 * @since 1.9.2
	 *
	 * @used-by wphb_clear_page_cache action.
	 *
	 * @param int|bool $post_id  Post ID.
	 */
	public function clear_cache_action( $post_id = false ) {
		if ( $post_id ) {
			$this->purge_post_cache( (int) $post_id );
		} else {
			$this->clear_cache();
		}
	}

	/**
	 * Toggle page caching.
	 *
	 * @since 1.8
	 *
	 * @used-by Page_Cache::enable()
	 * @used-by Page_Cache::disable()
	 * @used-by Installer::deactivate()
	 *
	 * @param bool $value   Value for page caching. Accepts boolean value: true or false.
	 * @param bool $network Value for network. Default: false.
	 */
	public function toggle_service( $value, $network = false ) {
		$options = parent::get_options();

		if ( is_multisite() ) {
			// We need to use this define for calls from Hub.
			$is_network_admin = defined( 'WPHB_IS_NETWORK_ADMIN' ) && WPHB_IS_NETWORK_ADMIN;
			if ( $network && ( is_network_admin() || $is_network_admin ) ) {
				// Updating for the whole network.
				$options['enabled']    = $value;
				$options['cache_blog'] = $value;
			} else {
				// Updating on subsite.
				if ( ! $options['enabled'] ) {
					// Page caching is turned off for the whole network, do not activate it per site.
					$options['cache_blog'] = false;
				} else {
					$options['cache_blog'] = $value;
				}
			}
		} else {
			$options['enabled'] = $value;
		}

		$this->update_options( $options );

		// Do not disable global cache, when disabled on subsite.
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		// Run activate/deactivate module actions.
		if ( $value ) {
			$this->activate();
		} else {
			$this->wpconfig_remove( 'WP_CACHE' );
			$this->cleanup();
		}
	}

	/**
	 * Clear cache for a specific page, when a comment is posted.
	 *
	 * @since 2.1.0
	 *
	 * @param int        $comment_id        The comment ID.
	 * @param int|string $comment_approved  1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param array      $commentdata       Comment data.
	 */
	public function clear_on_comment_post( $comment_id, $comment_approved, $commentdata ) {
		global $wphb_cache_config;

		// Option to clear cache on comment post is not set.
		if ( ! isset( $wphb_cache_config->comment_clear ) || ! $wphb_cache_config->comment_clear ) {
			return;
		}

		// Comment hasn't been approved, so it won't appear on the page just yet - no need to clear the cache.
		if ( 1 !== $comment_approved ) {
			return;
		}

		// Post ID is not set, nothing to clear - return.
		if ( ! isset( $commentdata['comment_post_ID'] ) || 0 === $commentdata['comment_post_ID'] ) {
			return;
		}

		$this->purge_post_cache( $commentdata['comment_post_ID'] );
	}

	/**
	 * Get module status.
	 *
	 * @param bool $current  Current status.
	 *
	 * @return bool
	 */
	public function module_status( $current ) {
		$options = Settings::get_settings( 'page_cache' );

		if ( false === $options['enabled'] ) {
			return false;
		}

		// Additional check for ajax (is_network_admin() does not work in ajax calls).
		$network_admin = is_network_admin();
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_SERVER['HTTP_REFERER'] ) && preg_match( '#^' . network_admin_url() . '#i', wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) { // Input var ok.
			$network_admin = true;
		}

		// If blog admins can't control cache settings, use global settings.
		if ( is_multisite() && ! $network_admin && 'blog-admins' === $options['enabled'] ) {
			$current = $options['cache_blog'];
		} else {
			$current = $options['enabled'];
		}

		return $current;
	}

	/**
	 * Gets the list of page headers being sent.
	 *
	 * @since 2.6.0
	 *
	 * @return array Empty array or list of headers
	 */
	public function get_page_headers() {
		if ( ! function_exists( 'headers_list' ) ) {
			return array();
		}

		$headers_list = headers_list();
		if ( empty( $headers_list ) || ! is_array( $headers_list ) ) {
			return array();
		}

		$headers = array();
		foreach ( $headers_list as $header ) {
			$pos = strpos( $header, ':' );

			if ( empty( $pos ) ) {
				continue;
			}

			$key = rtrim( substr( $header, 0, $pos ) );
			$val = ltrim( substr( $header, $pos + 1 ) );

			if ( ! empty( $headers[ $key ] ) ) {
				$val = $headers[ $key ] . ', ' . $val;
			}

			$headers[ $key ] = $val;
		}

		return $headers;
	}

	/**
	 * Get the headers from cached meta file if header caching is enabled and the cached file exists already.
	 *
	 * @since 2.6.0
	 *
	 * @return array Array of headers with array( Header => Header: Value ) syntax or empty array.
	 */
	public static function get_page_headers_cached() {
		global $wphb_meta_file, $wphb_cache_config;

		// If headers caching is disabled or the headers file doesn't exists.
		if ( ! $wphb_cache_config->cache_headers || ! file_exists( $wphb_meta_file ) ) {
			return array();
		}

		$headers     = array();
		$headers_raw = file_get_contents( $wphb_meta_file );

		/* Remove <?php die(); ?> from file */
		if ( 0 === strpos( $headers_raw, '<?php die(); ?>' ) ) {
			$headers = substr( $headers_raw, 15 );
		}
		$headers = (array) json_decode( $headers );

		if ( ! is_array( $headers ) ) {
			return array();
		}

		/**
		 * The only reason we do this is to please sir RIPS-a-Lot flagging this as code quality issue.
		 *
		 * Loop Iteration Change.
		 * If it is not intended to override the loop counter dynamically within the body, a new variable can be introduced and used.
		 */
		$new_headers = array();
		foreach ( $headers as $k => $v ) {
			$new_headers[ $k ] = "$k: $v";
		}
		unset( $headers );

		return $new_headers;
	}

}
