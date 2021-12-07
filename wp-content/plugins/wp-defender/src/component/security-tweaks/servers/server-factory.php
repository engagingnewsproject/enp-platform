<?php

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;
use Exception;

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
	private $servers = [];

	/**
	 * Constructor method.
	 *
	 * @param string $server
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
				sprintf( __( 'The <strong>%s</strong> server is not supported yet.', 'wpdef' ), $server )
			);

			// Using Apache as a fallback server not to showing errors.
			$server = 'apache';
		}

		$this->server = $this->servers[ $server ];
	}

	/**
	 * Get supported servers.
	 *
	 * @return array
	 */
	public function get_supported_servers() {
		$this->servers = apply_filters( 'defender_get_supported_servers', [
			'nginx'     => 'Nginx',
			'apache'    => 'Apache',
			'litespeed' => 'Apache', // We're going to use same server for Apache and LiteSpeed
			'iis-7'     => 'IIS_7',
			'flywheel'  => 'Flywheel',
			'cloudflare'=> 'Flywheel', // We're going to use same server for Flywheel and Cloudflare
		] );
	}

	/**
	 * Get the server for specific service.
	 *
	 * @param string
	 *
	 * @return WP_Defender\Component\Security_Tweaks\Servers\[$server]
	 */
	public function from( $service ) {
		$server = __NAMESPACE__ . '\\' . $this->server;

		if ( ! class_exists( $server ) ) {
			global $defender_server_not_supported;

			$defender_server_not_supported = new WP_Error(
				'defender_not_supported_server',
				sprintf( __( 'The <strong>%s</strong> server is not supported yet.', 'wpdef' ), $this->requested_server )
			);

			// Using Apache as a fallback server not to showing errors.
			$server = __NAMESPACE__ . '\\' . 'Apache';
		}

		return new $server( $service );
	}
}