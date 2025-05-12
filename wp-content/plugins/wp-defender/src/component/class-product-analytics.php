<?php
/**
 * Handles product analytics for the WP Defender plugin, specifically using Mixpanel for tracking.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WPMUDEV_Analytics_V4;
use Calotes\Base\Component;
use WP_Defender\Traits\Device;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Security_Tweaks\Servers\Server;

/**
 * Handles product analytics for the WP Defender plugin, specifically using Mixpanel for tracking.
 *
 * @since 4.2.0
 */
class Product_Analytics extends Component {

	use Device;

	private const PROJECT_TOKEN = '5d545622e3a040aca63f2089b0e6cae7';

	/**
	 * Holds the Mixpanel analytics instance.
	 *
	 * @var null|WPMUDEV_Analytics_V4
	 */
	private $mixpanel = null;
	/**
	 * Holds the MySQL version.
	 *
	 * @var string|null
	 */
	private $mysql_version;

	/**
	 * Constructor that initializes the Mixpanel instance and sets up the default properties.
	 */
	public function __construct() {
		if ( is_null( $this->mixpanel ) ) {
			// Create new mixpanel instance.
			$this->mixpanel = $this->prepare_mixpanel_instance();
		}
		$this->mixpanel->identify( $this->get_unique_id() );
		$this->mixpanel->registerAll( $this->get_super_properties() );
	}

	/**
	 * Get configured mixpanel instance.
	 *
	 * @return WPMUDEV_Analytics_v4
	 */
	public function get_mixpanel() {
		return $this->mixpanel;
	}

	/**
	 * Handle mixpanel error.
	 *
	 * @param  string $code  Error code.
	 * @param  string $data  Error data.
	 *
	 * @return void
	 */
	private function handle_error( $code, $data ) {
		$this->log( $code . ':' . $data, wd_internal_log() );
	}

	/**
	 * Prepare Mixpanel instance.
	 *
	 * @return WPMUDEV_Analytics_V4
	 */
	private function prepare_mixpanel_instance() {
		if ( ! class_exists( 'WPMUDEV_Analytics_V4' ) ) {
			require_once defender_path( 'extra/wpmudev-analytics/autoload.php' );
		}
		$extra_options  = array(
			'consumers' => array(
				'file'   => 'WPMUDEV_Analytics_Vendor\ConsumerStrategies_FileConsumer',
				'curl'   => 'WPMUDEV_Analytics_Vendor\ConsumerStrategies_CurlConsumer',
				'socket' => 'WPMUDEV_Analytics_Vendor\ConsumerStrategies_SocketConsumer',
			),
			'consumer'  => 'socket',
		);
		$this->mixpanel = new WPMUDEV_Analytics_V4( 'defender', 'Defender', 55, self::PROJECT_TOKEN, $extra_options );

		return $this->mixpanel;
	}

	/**
	 * Get super properties for all events.
	 *
	 * @return array
	 */
	private function get_super_properties(): array {
		global $wp_version;

		return array(
			'active_theme'       => get_stylesheet(),
			'locale'             => get_locale(),
			'mysql_version'      => $this->get_mysql_version(),
			'php_version'        => PHP_VERSION,
			'plugin'             => 'Defender',
			'plugin_type'        => ( new WPMUDEV() )->is_pro() ? 'pro' : 'free',
			'plugin_version'     => DEFENDER_VERSION,
			'server_type'        => Server::get_current_server(),
			'wp_type'            => is_multisite() ? 'multisite' : 'single',
			'wp_version'         => $wp_version,
			'memory_limit'       => $this->convert_to_megabytes( $this->get_memory_limit() ),
			'max_execution_time' => $this->get_max_execution_time(),
			'device'             => $this->get_device(),
			'user_agent'         => defender_get_user_agent(),
		);
	}

	/**
	 * Get unique identity for current site.
	 *
	 * @return string
	 */
	private function get_unique_id(): string {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', home_url() );

		return untrailingslashit( $url );
	}

	/**
	 * Retrieves the PHP memory limit in bytes and converts it to an integer.
	 *
	 * @return int The memory limit in bytes.
	 */
	private function get_memory_limit(): int {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || - 1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return (int) $memory_limit * 1024 * 1024;
	}

	/**
	 * Converts a size in bytes to megabytes.
	 *
	 * @param  int $size_in_bytes  The size in bytes.
	 *
	 * @return float The size in megabytes.
	 */
	private function convert_to_megabytes( $size_in_bytes ) {
		if ( empty( $size_in_bytes ) ) {
			return 0;
		}
		$unit_mb = pow( 1024, 2 );

		return round( $size_in_bytes / $unit_mb, 2 );
	}

	/**
	 * Retrieves the maximum execution time for PHP scripts from the ini settings.
	 *
	 * @return int The maximum execution time in seconds.
	 */
	private function get_max_execution_time() {
		return (int) ini_get( 'max_execution_time' );
	}

	/**
	 * Retrieves the MySQL version from the global $wpdb object.
	 *
	 * @return string The MySQL version.
	 */
	private function get_mysql_version() {
		if ( ! $this->mysql_version ) {
			global $wpdb;
			$this->mysql_version = $wpdb->db_version();
		}

		return $this->mysql_version;
	}
}