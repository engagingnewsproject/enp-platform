<?php
/**
 * Responsible for determining the server software
 * and type based on the environment or a self-ping mechanism.
 *
 * @package WP_Defender\Component\Security_Tweaks\Servers
 */

namespace WP_Defender\Component\Security_Tweaks\Servers;

/**
 * The Server class provides methods to determine the type of server on which WordPress is running.
 * It supports detection of server software through environment variables or by making a self-ping request.
 */
class Server {

	public const CACHE_CURRENT_SERVER = 'defender_current_server';

	/**
	 * Create a new instance of the Server_Factory class.
	 *
	 * @param  mixed $server  The server type.
	 *
	 * @return Server_Factory Returns an instance of Server_Factory.
	 */
	public static function create( $server ) {
		return new Server_Factory( $server );
	}

	/**
	 * Determine the server software by making a self-ping request to the home URL.
	 *
	 * @param  string $default_server_name  The default server name to return if detection fails. Defaults to 'apache'.
	 *
	 * @return string Returns the server software type or the default server name if detection fails.
	 */
	public static function get_software_by_self_ping( $default_server_name = 'apache' ) {
		$request = wp_remote_head(
			home_url(),
			array(
				'user-agent' => defender_get_user_agent( 'Defender Self Ping' ),
				// Most hosts don't really have valid ssl or ssl still pending.
				'sslverify'  => apply_filters( 'defender_ssl_verify', true ),
			)
		);
		$server  = wp_remote_retrieve_header( $request, 'server' );
		$server  = explode( '/', $server );

		if ( isset( $server[0] ) ) {
			$server = strtolower( $server[0] );
		} else {
			$server = $default_server_name;
		}

		return $server;
	}

	/**
	 * Get the server software from the SERVER_SOFTWARE server variable or by self-ping if not available.
	 *
	 * @return string Returns the server software type.
	 */
	public static function get_software() {
		$software = defender_get_data_from_request( 'SERVER_SOFTWARE', 's' );
		if ( empty( $software ) ) {
			return self::get_software_by_self_ping();
		}

		$software = explode( ' ', $software );
		$software = explode( '/', reset( $software ) );
		if ( isset( $software[0] ) ) {
			return strtolower( $software[0] );
		}
		return self::get_software_by_self_ping();
	}

	/**
	 * Determine the server.
	 *
	 * @return string
	 */
	public static function get_current_server() {
		$url         = home_url();
		$server_type = get_site_transient( self::CACHE_CURRENT_SERVER );

		if ( ! is_array( $server_type ) ) {
			$server_type = array();
		}

		if ( isset( $server_type[ $url ] ) && ! empty( $server_type[ $url ] ) ) {
			return strtolower( $server_type[ $url ] );
		}

		// Url should end with php.
		global $is_apache, $is_nginx, $is_IIS, $is_iis7;

		if ( $is_nginx ) {
			$server = 'nginx';
		} elseif ( $is_apache ) {
			// Case the url is detecting php file.
			if ( 'php' === pathinfo( $url, PATHINFO_EXTENSION ) ) {
				$server = 'apache';
			} else {
				$server = self::get_software();
			}
		} elseif ( $is_iis7 || $is_IIS ) {
			$server = 'iis-7';
		} else {
			$server = self::get_software();
		}

		$server_type[ $url ] = $server;
		set_site_transient( self::CACHE_CURRENT_SERVER, $server_type, 3600 );

		return $server;
	}

	/**
	 * Check whether ping test failed or not.
	 *
	 * @param  string $url  The URL to ping.
	 *
	 * @return bool
	 */
	public static function ping_test_failed( $url ) {
		$response = wp_remote_post( $url, array( 'user-agent' => 'WP Defender Self Ping Test' ) );

		if ( is_wp_error( $response ) ) {
			return true;
		}

		return 200 !== wp_remote_retrieve_response_code( $response );
	}
}