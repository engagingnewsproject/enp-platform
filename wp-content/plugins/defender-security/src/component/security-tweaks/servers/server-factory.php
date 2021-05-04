<?php

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;
use Exception;

class Server_Factory {
	/**
	 * Server name holder
	 *
	 * @var string|default null
	 */
	private $server = null;

	/**
	 * Supported server list holder
	 *
	 * @var string|default null
	 */
	private $servers = [];

	/**
	 * Constructor method
	 *
	 * @param string $server
	 *
	 * @return void
	 */
	public function __construct( $server ) {
		$this->get_supported_servers();
		if ( empty( $this->servers[ $server ] ) ) {
			wp_die(
				new WP_Error(
					'defender_not_supported_server',
					sprintf( __( 'This %s is not supported yet', 'wpdef' ), $server )
				)
			);
		}

		$this->server = $this->servers[ $server ];
	}

	/**
	 * Get supported servers
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
	 * Get the server for specific service
	 *
	 * @param string
	 *
	 * @return WP_Defender\Component\Security_Tweaks\Servers\[$server]
	 */
	public function from( $service ) {
		$server = __NAMESPACE__ . '\\' . $this->server;

		if ( ! class_exists( $server ) ) {
			wp_die(
				new WP_Error(
					'defender_not_supported_server',
					sprintf( __( 'This %s is not supported yet', 'wpdef' ), $server )
				)
			);
		}

		return new $server( $service );
	}
}
