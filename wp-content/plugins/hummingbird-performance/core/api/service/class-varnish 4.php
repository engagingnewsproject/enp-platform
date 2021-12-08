<?php
/**
 * Varnish cache service.
 *
 * @since 2.1.0
 * @package Hummingbird
 */

namespace Hummingbird\Core\Api\Service;

use Hummingbird\Core\Api\Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Varnish
 */
class Varnish extends Service {

	/**
	 * Service name.
	 *
	 * @var string $name
	 */
	protected $name = 'varnish';

	/**
	 * Varnish constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		$this->request = new \Hummingbird\Core\Api\Request\Varnish( $this );
	}

	/**
	 * Purge cache.
	 *
	 * @since 2.1.0
	 *
	 * @param string $path  Page to purge.
	 *
	 * @return mixed
	 */
	public function purge_cache( $path ) {
		$this->request->set_timeout( 2 );
		// Try the CURL PURGE/PURGEALL method.
		$this->request->clear_cache( trailingslashit( $path ) );
		// Try the remote request PURGE call.
		return $this->request->purge( trailingslashit( $path ) . '.*' );
	}

}
