<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/security/security-checks.php';
require_once __DIR__ . '/wpe-common-adapter.php';

\wpengine\cache_plugin\check_security();

class CacheViewHelper {

	public static function is_current_site_password_protected() {
		$site_name = WpeCommonAdapter::get_instance()->get_site_name();
		$url       = $site_name . '.wpengine.com';
		$http_code = intval( self::get_http_code( $url ) );
		if ( 401 === $http_code || 403 === $http_code ) {
			return true;
		}
	}

	public static function get_http_code( $url ) {

		try {
			// phpcs:disable WordPress.WP.AlternativeFunctions
			$handle = curl_init( $url );
			curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
			$response  = curl_exec( $handle );
			$http_code = curl_getinfo( $handle, CURLINFO_HTTP_CODE );
			curl_close( $handle );
			return $http_code;
		} catch ( \Exception $e ) {
			log_error( "Caught exception while curling url: {$e->getMessage()} {$e->getTraceAsString()}" );
		}

	}

}
