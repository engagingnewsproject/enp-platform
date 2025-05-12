<?php
/**
 * Responsible for handling server-specific operations
 * within the WP_Defender plugin. It provides functionality to identify and utilize supported server configurations.
 *
 * @package WP_Defender\Component\Security_Tweaks\Servers
 */

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;

/**
 * Class Server_Factory
 * Handles the creation and management of server instances based on the server environment.
 */
class Server_Factory {

	/**
	 * Server name holder for showing notice.
	 *
	 * @var string
	 */
	private $requested_server;

	/**
	 * Server name holder.
	 *
	 * @var string
	 */
	private $server = null;

	/**
	 * Supported server list holder.
	 *
	 * @var array
	 */
	private $servers = array();

	/**
	 * Constructor method.
	 *
	 * @param  string $server  The name of the server to handle.
	 *
	 * @return void
	 */
	public function __construct( $server ) {
		$this->requested_server = $server;

		$this->get_supported_servers();

		if ( empty( $this->servers[ $server ] ) ) {
			global $defender_server_not_supported;

			$defender_server_not_supported = new WP_Error(
				'defender_not_supported_server',
				sprintf(
				/* translators: %s: Server name. */
					esc_html__( 'The %s server is not supported yet.', 'wpdef' ),
					'<strong>' . $server . '</strong>'
				)
			);
			// Using Apache as a fallback server not to showing errors.
			$server = 'apache';
		}

		$this->server = $this->servers[ $server ];
	}

	/**
	 * Get supported servers.
	 *
	 * @return void
	 */
	public function get_supported_servers() {
		$this->servers = apply_filters(
			'defender_get_supported_servers',
			array(
				'nginx'      => 'Nginx',
				'unit'       => 'Nginx', // We're going to use the same server for Nginx and Unit.
				'apache'     => 'Apache',
				'litespeed'  => 'Apache', // We're going to use the same server for Apache and LiteSpeed.
				'iis-7'      => 'IIS_7',
				'flywheel'   => 'Flywheel',
				'cloudflare' => 'Flywheel', // We're going to use the same server for Flywheel and Cloudflare.
				// A specific case for WordPress Playground.
				'php.wasm'   => 'PHP_Wasm',
			)
		);
	}

	/**
	 * Get the server for specific service. Return WP_Defender\Component\Security_Tweaks\Servers\[$server].
	 *
	 * @param  string $service  The service for which the server instance is needed.
	 */
	public function from( $service ) {
		$server = __NAMESPACE__ . '\\' . $this->server;

		if ( ! class_exists( $server ) ) {
			global $defender_server_not_supported;

			$defender_server_not_supported = new WP_Error(
				'defender_not_supported_server',
				sprintf(
				/* translators: %s: Server name. */
					esc_html__( 'The %s server is not supported yet.', 'wpdef' ),
					'<strong>' . $this->requested_server . '</strong>'
				)
			);

			// Using Apache as a fallback server not to showing errors.
			$server = __NAMESPACE__ . '\\Apache';
		}

		return new $server( $service );
	}
}