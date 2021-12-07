<?php
/**
 * A parent class for those modules that offers a piece of code to
 * setup the server (gzip and caching)
 *
 * @package Hummingbird\Core
 */

namespace Hummingbird\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Module_Server extends Module
 */
abstract class Module_Server extends Module {

	/**
	 * Module slug (used in transient).
	 *
	 * @var bool|string $transient_slug
	 */
	protected $transient_slug = false;

	/**
	 * Module status.
	 *
	 * @var array $status
	 */
	protected $status;

	/**
	 * Execute the module actions. It must be defined in subclasses. Executed when module is active.
	 */
	public function run() {}

	/**
	 * Initializes the module. Always executed even if the module is deactivated.
	 *
	 * Do not use __construct in subclasses, use init() instead
	 */
	public function init() {}

	/**
	 * Return the analyzed data for the module
	 *
	 * @param bool $force If set to true, cache will be cleared before getting the data.
	 * @param bool $check_api If set to true, the api will be checked.
	 *
	 * @return mixed Analysis data
	 */
	public function get_analysis_data( $force = false, $check_api = false ) {
		if ( ! $this->transient_slug ) {
			return false;
		}

		$transient = 'wphb-' . $this->transient_slug . '-data';

		$this->status = get_site_option( $transient );

		if ( $force || ! $this->status || $check_api ) {
			$this->clear_cache();

			$this->status = $this->analyze_data( $check_api );

			update_site_option( $transient, $this->status );

			return $this->status;
		}

		return $this->status;
	}

	/**
	 * Analyze the data
	 *
	 * @param bool $check_api If set to true, the api will be checked.
	 *
	 * @return mixed
	 */
	abstract protected function analyze_data( $check_api = false );

	/**
	 * Implement abstract parent method for clearing cache.
	 */
	public function clear_cache() {
		delete_site_option( 'wphb-' . $this->transient_slug . '-data' );
	}

	/**
	 * Get the server code snippet
	 *
	 * @param string $server Server name (nginx,apache...).
	 * @param array  $expiry_times Type expiry times (javascript, css...).
	 *
	 * @return string
	 */
	public function get_server_code_snippet( $server, $expiry_times = array() ) {
		$method = 'get_' . str_replace( array( '-', ' ' ), '', strtolower( $server ) ) . '_code';
		if ( ! method_exists( $this, $method ) ) {
			return '';
		}

		return call_user_func_array( array( $this, $method ), array( $expiry_times ) );
	}

	/**
	 * Return the server type (Apache, NGINX...)
	 *
	 * @param bool $check_for_cloudflare  Should we check for Cloudflare.
	 *
	 * @return string Server type
	 */
	public static function get_server_type( $check_for_cloudflare = true ) {
		global $is_apache, $is_IIS, $is_iis7, $is_nginx;

		$type = '';

		if ( $is_apache ) {
			// It's a common configuration to use nginx in front of Apache.
			// Let's make sure that this server is Apache.
			$response = wp_remote_get( home_url() );

			if ( is_wp_error( $response ) ) {
				// Bad luck.
				$type = 'apache';
			} else {
				$server = strtolower( wp_remote_retrieve_header( $response, 'server' ) );

				if ( false !== strpos( $server, 'nginx' ) ) {
					$type = 'nginx';
				} elseif ( false !== strpos( $server, 'litespeed' ) ) {
					$type = 'litespeed';
				} else {
					$type = 'apache';
				}
			}
		} elseif ( $is_nginx ) {
			$type = 'nginx';
		} elseif ( $is_IIS ) {
			$type = 'IIS';
		} elseif ( $is_iis7 ) {
			$type = 'IIS 7';
		}

		if ( $check_for_cloudflare && apply_filters( 'wp_hummingbird_is_active_module_cloudflare', false ) ) {
			$type = 'cloudflare';
		}

		return $type;
	}

	/**
	 * Get a list of server types
	 *
	 * @return array
	 */
	public static function get_servers() {
		return array(
			'apache'     => 'Apache/LiteSpeed',
			'nginx'      => 'NGINX',
			'iis'        => 'IIS',
			'cloudflare' => 'Cloudflare',
		);
	}

	/**
	 * Get code snippet for a module and server type
	 *
	 * @param string $module Module name.
	 * @param string $server_type Server type (nginx, apache...).
	 * @param array  $expiry_times Type expiry times (javascript, css...).
	 *
	 * @return string Code snippet
	 */
	public static function get_code_snippet( $module, $server_type = '', $expiry_times = array() ) {
		$module = Utils::get_module( $module );
		if ( ! $module ) {
			return '';
		}

		if ( ! $server_type ) {
			$server_type = self::get_server_type();
		}

		return apply_filters( 'wphb_code_snippet', $module->get_server_code_snippet( $server_type, $expiry_times ), $server_type, $module );
	}

	/**
	 * Check if .htaccess is writable.
	 *
	 * @return bool
	 */
	public static function is_htaccess_writable() {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$home_path = get_home_path();
		return ( ! file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) ) || is_writable( $home_path . '.htaccess' );
	}

	/**
	 * Check if .htaccess has Hummingbird caching or gzip rules in place.
	 *
	 * @param string $module  Module slug.
	 *
	 * @return bool
	 */
	public static function is_htaccess_written( $module = '' ) {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'extract_from_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$existing_rules = array_filter( extract_from_markers( get_home_path() . '.htaccess', 'WP-HUMMINGBIRD-' . strtoupper( $module ) ) );
		return ! empty( $existing_rules );
	}

	/**
	 * Add rules .htaccess file.
	 *
	 * @param string $module  Gzip or caching module.
	 *
	 * @return bool
	 */
	public static function save_htaccess( $module ) {
		if ( self::is_htaccess_written( $module ) ) {
			return false;
		}

		$htaccess_file = get_home_path() . '.htaccess';

		if ( self::is_htaccess_writable() ) {
			$code = self::get_code_snippet( $module, 'apache' );
			$code = explode( "\n", $code );
			return insert_with_markers( $htaccess_file, 'WP-HUMMINGBIRD-' . strtoupper( $module ), $code );
		}

		return false;
	}

	/**
	 * Remove rules from .htaccess file.
	 *
	 * @param string $module  Module.
	 *
	 * @return bool
	 */
	public static function unsave_htaccess( $module ) {
		if ( ! self::is_htaccess_written( $module ) ) {
			return false;
		}

		$htaccess_file = get_home_path() . '.htaccess';

		if ( self::is_htaccess_writable() ) {
			return insert_with_markers( $htaccess_file, 'WP-HUMMINGBIRD-' . strtoupper( $module ), '' );
		}

		return false;
	}

}
