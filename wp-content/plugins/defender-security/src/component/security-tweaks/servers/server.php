<?php

namespace WP_Defender\Component\Security_Tweaks\Servers;

class Server {
	public static function create( $server ) {
		return new Server_Factory( $server );
	}

	/**
	 * @param string $default_server_name
	 *
	 * @return string
	 */
	public static function get_software_by_self_ping( $default_server_name = 'apache' ) {
		$request = wp_remote_head(
			home_url(),
			array(
				'user-agent' => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'Defender Self Ping',
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
	 * @return string
	 */
	public static function get_software() {
		if ( empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return self::get_software_by_self_ping();
		}

		$server = explode( ' ', $_SERVER['SERVER_SOFTWARE'] );
		$server = explode( '/', reset( $server ) );
		if ( isset( $server[0] ) ) {
			return strtolower( $server[0] );
		} else {
			return self::get_software_by_self_ping();
		}
	}

	/**
	 * Determine the server.
	 *
	 * @return string
	 */
	public static function get_current_server() {
		$url         = home_url();
		$server_type = get_site_transient( 'defender_current_server' );

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
		set_site_transient( 'defender_current_server', $server_type, 3600 );

		return $server;
	}

	/**
	 * Check whether ping test failed or not.
	 *
	 * @param string $url
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
