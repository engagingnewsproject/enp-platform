<?php
/**
 * Provides connection to WPMU API to perform queries against Hosting endpoints.
 *
 * @sice 3.3.1
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Api\Service;

use Hummingbird\Core\Api\Exception;
use Hummingbird\Core\Api\Request\WPMUDEV;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Hosting extends Service.
 */
class Hosting extends Service {
	/**
	 * Endpoint name.
	 *
	 * @var string $name
	 */
	public $name = 'hub';

	/**
	 * API version.
	 *
	 * @access private
	 *
	 * @var string $version
	 */
	private $version = 'v1';

	/**
	 * Performance constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		$this->request = new WPMUDEV( $this );
	}

	/**
	 * Getter method for api version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get hosting info.
	 *
	 * @param int $site_id  Site ID.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_info( $site_id ) {
		return $this->request->get(
			'sites/' . $site_id . '/modules/hosting',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Disable FastCGI.
	 *
	 * @param int $site_id  Site ID.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function disable_fast_cgi( $site_id ) {
		$this->request->add_post_argument( 'is_active', false );
		return $this->request->put(
			'sites/' . $site_id . '/modules/hosting/static-cache',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

}