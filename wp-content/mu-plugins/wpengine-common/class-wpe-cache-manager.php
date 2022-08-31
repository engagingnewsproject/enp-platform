<?php
/**
 * WpCli
 *
 * @package wpengine/common-mu-plugin
 */

namespace wpe\plugin;

/**
 * Class Wpe_Cache_Manager
 */
class Wpe_Cache_Manager {

	/**
	 * Instance of Wp_Abstraction
	 *
	 * @var Wp_Abstraction
	 */
	public $wp_abstraction;

	/**
	 * Instance of Wp_Cli_Abstraction
	 *
	 * @var Wp_Cli_Abstraction
	 */
	public $wp_cli_abstraction;

	/**
	 * Constructor
	 *
	 * @param Wp_Abstraction     $wp_abstraction Instance of Wp_Abstraction.
	 * @param Wp_Cli_Abstraction $wp_cli_abstraction Instance of Wp_Cli_Abstraction.
	 */
	public function __construct(
		Wp_Abstraction $wp_abstraction,
		Wp_Cli_Abstraction $wp_cli_abstraction
	) {
		$this->wp_abstraction     = $wp_abstraction;
		$this->wp_cli_abstraction = $wp_cli_abstraction;
	}

	/**
	 * Add action
	 *
	 * Hook into WordPress and WP CLI actions to execute remote object cache purge
	 *
	 * @param Wp_Abstraction     $wp_abstraction Instance of Wp_Abstraction.
	 * @param Wp_Cli_Abstraction $wp_cli_abstraction Instance of Wp_Cli_Abstraction.
	 */
	public static function add_action(
		Wp_Abstraction $wp_abstraction,
		Wp_Cli_Abstraction $wp_cli_abstraction
	) : void {
		$object_cache_enabled = $wp_abstraction->wp_using_ext_object_cache();
		if ( $object_cache_enabled ) {
			$wpe_cache_manager = new Wpe_Cache_Manager( $wp_abstraction, $wp_cli_abstraction );
			add_action( 'wp_upgrade', array( $wpe_cache_manager, 'remote_object_cache_purge' ) );
			$wp_cli_abstraction->add_hook( 'after_invoke:cache flush', array( $wpe_cache_manager, 'remote_object_cache_purge' ) );
		}
	}

	/**
	 * WPE Remote Object Cache Purge
	 *
	 * This is intended to be used on cluster utility nodes that do not have direct access to memcached.
	 * We believe this workaround will no longer be necessary when CA-3151 is completed.
	 *
	 * @return void
	 */
	public function remote_object_cache_purge() : void {
		$memcached_available = $this->wp_abstraction->wp_cache_set( 'wpe_cache_test', 'value' );
		if ( ! $memcached_available ) {
			$response              = $this->wp_abstraction->wp_remote_request(
				$this->get_remote_object_cache_purge_url()
			);
			$default_error_message = 'There was an error clearing object cache on webs';
			if ( $this->wp_abstraction->is_wp_error( $response ) ) {
				$this->wp_cli_abstraction->log( $response->get_error_message() );
				$this->wp_cli_abstraction->error( $default_error_message );
			} elseif ( ! array_key_exists( 'body', $response ) ) {
				$this->wp_cli_abstraction->log( 'Missing response body' );
				$this->wp_cli_abstraction->error( $default_error_message );
			} elseif ( strpos( $response['body'], 'ERROR' ) !== false ) {
				$this->wp_cli_abstraction->log( $response['body'] );
				$this->wp_cli_abstraction->error( $default_error_message );
			}
		}
	}

	/**
	 * Get Remote Object Cache Purge URL
	 *
	 * Provides an endpoint that can be called to purge object cache
	 *
	 * @return string
	 */
	public function get_remote_object_cache_purge_url() {
		$site_url = $this->wp_abstraction->get_site_url();
		$action   = 'purge-object-cache';
		return "{$site_url}/?wp-cmd={$action}";
	}
}
