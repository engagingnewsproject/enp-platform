<?php
/**
 * Plugin Name: WP Engine Security Auditor
 * Description: WP Engine-specific security auditing and logging
 * Author:      wpengine
 * Author URI:  https://wpengine.com
 * Version:     1.0.8
 *
 * @package wpengine-security-auditor
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( defined( 'WPENGINE_SECURITY_AUDITOR_ENABLED' ) && WPENGINE_SECURITY_AUDITOR_ENABLED !== true ) {
	return;
}

WPEngineSecurityAuditor_Events::initialize();
WPEngineSecurityAuditor_Scans::initialize();

/**
 * Class WPEngineSecurityAuditor_Events
 */
class WPEngineSecurityAuditor_Events {

	/**
	 * Singleton instance of this class
	 *
	 * @var WPEngineSecurityAuditor_Events
	 */
	public static $instance;

	/**
	 * Initialize
	 *
	 * This is called on plugin start to create a singleton.
	 */
	public static function initialize() {
		if ( defined( 'WP_CLI' ) && WP_CLI && getenv( 'WPENGINE_SECURITY_AUDITOR_LOGGING' ) !== 'enabled' ) {
			// logging events to an interactive session is just noise
			return;
		}
		self::$instance = new self();
		foreach ( self::$instance->events as $event => $nargs ) {
			if ( is_array( $nargs ) ) {
				$nargs = count( $nargs );
			}
			add_action( $event, [ self::$instance, "on_$event" ], 10, $nargs );
		}
	}

	/**
	 * WPEngineSecurityAuditor_Events constructor.
	 */
	public function __construct() {
		$this->events = [
			// code changes
			'activated_plugin'          => [ 'plugin', 'network_activation' ],
			'deactivated_plugin'        => [ 'plugin', 'network_activation' ],
			'switch_theme'              => [ 'new_name' ],
			'upgrader_process_complete' => [ 'upgrader', 'info' ], // special cased
			// access changes
			'add_user_role'             => [ 'user_id', 'role' ],
			'remove_user_role'          => [ 'user_id', 'role' ],
			'set_user_role'             => [ 'user_id', 'role', 'old_roles' ],
			'granted_super_admin'       => [ 'user_id' ],
			'revoked_super_admin'       => [ 'user_id' ],
			// account changes
			'wp_login'                  => [ 'user_login', 'user' ], // special cased
			'user_register'             => [ 'user_id' ],
			'profile_update'            => [ 'user_id' ],
			'deleted_user'              => [ 'user_id', 'reassign' ],
			'retrieve_password_key'     => [ 'user_login' ],
			// option changes
			'updated_option'            => [ 'option', 'old_value', 'value' ],
			'added_option'              => [ 'option', 'value' ],
			'deleted_option'            => [ 'option' ],
		];
		$this->tracked_options = [
			'users_can_register' => 1,
			'default_role'       => 1,
			'siteurl'            => 1,
			'home'               => 1,
		];
	}

	/**
	 * Get current WordPress User
	 *
	 * @return WP_User|null
	 */
	private function get_current_user() {
		// when events fire before all plugins have loaded, unless already overridden
		// the pluggable.php functions will not yet be defined.
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			return null;
		}
		return wp_get_current_user();
	}

	/**
	 * Log event to the error log
	 *
	 * NOTE: This method can be run before much of WordPress has loaded.
	 * Be sure that WP functions used here are imported before mu plugins are loaded.
	 *
	 * @param string $event Name of the event
	 * @param array  $attrs Data associated with the event
	 *
	 * @see https://github.com/WordPress/WordPress/blob/d0c1b77cda3f4ea0b502d2abd686e4ddd45dad7e/wp-settings.php#L292-L305
	 */
	public function log_event( $event, $attrs ) {
		$attrs['blog_id'] = get_current_blog_id();
		$attrs['event']   = $event;
		$u                = $this->get_current_user();
		if ( $u ) {
			$attrs['current_user_id'] = $u->ID;
		}
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$attrs['remote_addr'] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		error_log( "auditor:event=$event " . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Log unexpected object context calls to this class
	 *
	 * @param string $name Attempted method call
	 * @param array  $args Args used in method call
	 */
	public function __call( $name, $args ) {
		if ( 'on_' !== substr( $name, 0, 3 ) || ! array_key_exists( substr( $name, 3 ), $this->events ) ) {
			error_log( "unexpected invocation of WPEngineSecurityAuditor_Events::$name" );
			return;
		}
		$event         = substr( $name, 3 );
		$expected_args = $this->events[ $event ];
		// pad arguments with null in case of non-conforming events
		$args = array_pad( $args, count( $expected_args ), null );
		// truncate arguments in case of additional parameters
		$args  = array_slice( $args, 0, count( $expected_args ) );
		$attrs = array_combine( $expected_args, $args );
		$this->log_event( $event, $attrs );
	}

	/**
	 * Log on wp_login hook
	 *
	 * Arguments elided to accomodate non-conforming events
	 *
	 * param $user_login string
	 * param $user WP_User
	 *
	 * @see https://developer.wordpress.org/reference/hooks/wp_login/
	 */
	public function on_wp_login() {
		if ( func_num_args() < 2 ) {
			// ignoring non-conforming event
			return;
		}
		$user = func_get_arg( 1 );
		// again, ignoring non-comforming events
		if ( $user instanceof WP_User ) {
			$this->log_event( 'wp_login', [ 'user_id' => $user->ID ] );
		}
	}

	/**
	 * Log on upgrader_process_complete hook
	 *
	 * @param WP_Upgrader $upgrader WP_Upgrader instance. Might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance
	 * @param array       $info Array of bulk item update data.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 */
	public function on_upgrader_process_complete( $upgrader, $info ) {
		$action = $info['action'];
		$type   = $info['type'];
		switch ( $type ) {
			case 'core':
				global $wp_version, $wp_db_version;
				$info['wp_version']    = $wp_version;
				$info['wp_db_version'] = $wp_db_version;
				$this->log_event( 'upgrader_process_complete_core', $info );
				break;
			case 'plugin':
				$plugins = $info['plugins'];
				unset( $info['plugins'] );
				if ( ! is_array( $plugins ) ) {
					$plugins = [ $upgrader->result['destination_name'] ];
				}
				foreach ( $plugins as $plugin ) {
					$info['plugin'] = $plugin;
					$this->log_event( 'upgrader_process_complete_plugin', $info );
				}
				break;
			case 'theme':
				$themes = $info['themes'];
				unset( $info['themes'] );
				if ( ! is_array( $themes ) ) {
					$themes = [ $themes ];
				}
				foreach ( $themes as $theme ) {
					$info['theme'] = $theme;
					$this->log_event( 'upgrader_process_complete_theme', $info );
				}
				break;
			default:
				error_log( "unrecognized upgrade type=$type" );
		}
	}

	/**
	 * Track only certain options
	 *
	 * @param string $option Name of the option
	 * @return bool
	 */
	private function is_tracked_option( $option ) {
		return array_key_exists( $option, $this->tracked_options );
	}

	/**
	 * Log on updated_option hook
	 *
	 * @param string $option Name of the option
	 * @param mixed  $old_value Previous value of the option
	 * @param mixed  $value Value of the option
	 *
	 * @see https://developer.wordpress.org/reference/hooks/updated_option/
	 */
	public function on_updated_option( $option, $old_value, $value ) {
		if ( ! $this->is_tracked_option( $option ) ) {
			return;
		}

		$this->log_event(
			'updated_option',
			[
				'option'    => $option,
				'old_value' => $old_value,
				'value'     => $value,
			]
		);
	}

	/**
	 * Log on added_option hook
	 *
	 * @param string $option Name of the option
	 * @param mixed  $value Value of the option
	 *
	 * @see https://developer.wordpress.org/reference/hooks/added_option/
	 */
	public function on_added_option( $option, $value ) {
		if ( ! $this->is_tracked_option( $option ) ) {
			return;
		}

		$this->log_event(
			'added_option',
			[
				'option' => $option,
				'value'  => $value,
			]
		);
	}

	/**
	 * Log on delete_option hook
	 *
	 * @param string $option Name of the option
	 *
	 * @see https://developer.wordpress.org/reference/hooks/on_deleted_option/
	 */
	public function on_deleted_option( $option ) {
		if ( ! $this->is_tracked_option( $option ) ) {
			return;
		}

		$this->log_event( 'deleted_option', [ 'option' => $option ] );
	}
}

/**
 * Class WPEngineSecurityAuditor_Scans
 */
class WPEngineSecurityAuditor_Scans {

	/**
	 * Singleton instance of this class
	 *
	 * @var WPEngineSecurityAuditor_Scans
	 */
	public static $instance;

	/**
	 * Initialize
	 *
	 * This is called on plugin start to create a singleton.
	 * We also setup WP Crons here.
	 */
	public static function initialize() {
		self::$instance = new self();
		add_action( 'WPEngineSecurityAuditor_Scans_scheduler', [ self::$instance, 'schedule_fingerprint' ] );
		add_action( 'WPEngineSecurityAuditor_Scans_fingerprint', [ self::$instance, 'fingerprint' ] );
		add_action( 'WPEngineSecurityAuditor_Scans_fingerprint_core', [ self::$instance, 'fingerprint_core' ] );
		add_action( 'WPEngineSecurityAuditor_Scans_fingerprint_plugins', [ self::$instance, 'fingerprint_plugins' ] );
		add_action( 'WPEngineSecurityAuditor_Scans_fingerprint_themes', [ self::$instance, 'fingerprint_themes' ] );
		add_action( 'init', [ self::$instance, 'ensure_scheduled' ] );
	}

	/**
	 * WPEngineSecurityAuditor_Scans constructor.
	 */
	public function __construct() {
	}

	/**
	 * Ensure fingerprints are setup in WP Cron
	 */
	public function ensure_scheduled() {
		if ( ! wp_next_scheduled( 'WPEngineSecurityAuditor_Scans_scheduler' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'WPEngineSecurityAuditor_Scans_scheduler' );
		}
	}

	/**
	 * Schedule Fingerprint
	 *
	 * Setup WordPress cron for fingerprinting
	 */
	public function schedule_fingerprint() {
		// do scans at random times in the next 12 hours
		wp_schedule_single_event( time() + random_int( 1, 60 * 60 * 12 ), 'WPEngineSecurityAuditor_Scans_fingerprint_core' );
		wp_schedule_single_event( time() + random_int( 1, 60 * 60 * 12 ), 'WPEngineSecurityAuditor_Scans_fingerprint_plugins' );
		wp_schedule_single_event( time() + random_int( 1, 60 * 60 * 12 ), 'WPEngineSecurityAuditor_Scans_fingerprint_themes' );
	}

	/**
	 * Create tree hash
	 *
	 * @param string $path Location of dir to hash
	 * @return string Hash
	 */
	public static function sig_tree( $path ) {
		$hc    = hash_init( 'sha256' );
		$queue = [ [ $path, '.', 0 ] ];
		$n     = 0;

		while ( $queue ) {
			list($path, $name, $parent) = array_pop( $queue );
			if ( is_link( $path ) ) {
				$content = readlink( $path );
			} elseif ( is_file( $path ) ) {
				$content = "sha256\0" . hash_file( 'sha256', $path );
			} elseif ( is_dir( $path ) ) {
				$me      = $n++;
				$content = "\0 0";
				foreach ( scandir( $path ) as $name ) {
					if ( '.' === $name || '..' === $name ) {
						continue;
					}
					$queue[] = [ "$path/$name", $name, $me ];
				}
			} else {
				$content = "\0 1"; // wtf
			}
			hash_update( $hc, "$name\0$parent\0$content\0" );
		}
		hash_update( $hc, $n );
		return 'v1:' . hash_final( $hc );
	}

	/**
	 * Send info to error log
	 *
	 * @param string $kind Kind of scan being recorded (ex. mu-plugins)
	 * @param string $name Name of scanned item (ex. WP Engine Security Auditor)
	 * @param string $slug Slug of scanned item (ex. wpengine-security-auditor)
	 * @param string $ver Version of scanned item (ex. 1.0.4)
	 * @param string $sig Hash of scanned item
	 */
	public function log_info( $kind, $name, $slug, $ver, $sig ) {
		$data = [
			'blog_id' => get_current_blog_id(),
			'kind'    => $kind,
			'name'    => $name,
			'slug'    => $slug,
			'ver'     => $ver,
			'sig'     => $sig,
		];
		error_log( 'auditor:scan=fingerprint ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Get plugin root dir
	 *
	 * @param string $plugin_file Full path of plugin file
	 * @return string
	 */
	private static function plugin_root( $plugin_file ) {
		$dir = dirname( $plugin_file );
		if ( WP_PLUGIN_DIR === $dir || WPMU_PLUGIN_DIR === $dir ) {
			return $plugin_file;
		}
		return $dir;
	}

	/**
	 * Fingerprint factory
	 */
	public function fingerprint() {
		// XXX legacy function to execute stale cron actions
		$this->fingerprint_plugins();
		$this->fingerprint_themes();
		$this->fingerprint_core();
	}

	/**
	 * Fingerprint plugins
	 */
	public function fingerprint_plugins() {
		if ( ! is_main_site() ) {
			// only calculate fingerprints for the main site.
			// XXX this skips per-blog activations states
			// XXX still recalculates fingerprints in a multinetwork
			return;
		}

		$plugin_headers = [
			'name' => 'Plugin Name',
			'ver'  => 'Version',
		];

		foreach ( wp_get_mu_plugins() as $path ) {
			$pd   = get_file_data( $path, $plugin_headers );
			$slug = plugin_basename( $path );
			$this->log_info( 'mu-plugin', $pd['name'], $slug, $pd['ver'], self::sig_tree( $path ) );
		}

		$scanned_path = [];
		foreach ( ( is_multisite() ? wp_get_active_network_plugins() : [] ) as $path ) {
			$pd                    = get_file_data( $path, $plugin_headers );
			$slug                  = plugin_basename( $path );
			$path                  = self::plugin_root( $path );
			$scanned_path[ $path ] = 1;
			$this->log_info( 'active-network-plugin', $pd['name'], $slug, $pd['ver'], self::sig_tree( $path ) );
		}

		foreach ( wp_get_active_and_valid_plugins() as $full_path ) {
			$pd   = get_file_data( $full_path, $plugin_headers );
			$slug = plugin_basename( $full_path );
			$path = self::plugin_root( $full_path );
			if ( array_key_exists( $path, $scanned_path ) ) {
				continue;
			}
			$scanned_path[ $path ] = 1;
			$this->log_info( 'active-plugin', $pd['name'], $slug, $pd['ver'], self::sig_tree( $path ) );
		}

		foreach ( get_plugins() as $plugin_path => $data ) {
			$full_path = WP_PLUGIN_DIR . '/' . $plugin_path;
			$slug      = plugin_basename( $full_path );
			$path      = self::plugin_root( $full_path );
			if ( array_key_exists( $path, $scanned_path ) ) {
				continue;
			}
			$this->log_info( 'installed-plugin', $data['Name'], $slug, $data['Version'], self::sig_tree( $path ) );
		}

	}

	/**
	 * Fingerprint Themes
	 */
	public function fingerprint_themes() {
		if ( ! is_main_site() ) {
			// only calculate fingerprints for the main site.
			// XXX this skips per-blog activations states
			// XXX still recalculates fingerprints in a multinetwork
			return;
		}

		foreach ( wp_get_themes() as $theme_name => $theme ) {
			$this->log_info( 'theme', $theme->name, $theme->get_stylesheet(), $theme->version, self::sig_tree( $theme->get_stylesheet_directory() ) );
		}

	}

	/**
	 * Fingerprint WP Core
	 */
	public function fingerprint_core() {
		if ( ! is_main_site() ) {
			// only calculate fingerprints for the main site.
			// XXX this skips per-blog activations states
			// XXX still recalculates fingerprints in a multinetwork
			return;
		}

		global $wp_version;

		$this->log_info( 'wp-core', 'wp-includes', 'wp-includes', $wp_version, self::sig_tree( ABSPATH . WPINC ) );
		$this->log_info( 'wp-core', 'wp-admin', 'wp-admin', $wp_version, self::sig_tree( ABSPATH . '/wp-admin' ) );
	}
}
