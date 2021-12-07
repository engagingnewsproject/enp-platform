<?php
/**
 * Provides connection to WPMU API to perform queries agains performance endpoint.
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
 * Class Performance extends Service.
 */
class Performance extends Service {

	/**
	 * Endpoint name.
	 *
	 * @var string $name
	 */
	public $name = 'performance';

	/**
	 * API version.
	 *
	 * @access private
	 * @var    string $version
	 */
	private $version = 'v2';

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
	 * Check if performance test has finished on server.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function check() {
		return $this->request->post(
			'site/check/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Ping to Performance Module so it starts to gather data.
	 *
	 * @since 1.8.1 Changed timeout from 0.1 to 2 seconds.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function ping() {
		$this->request->set_timeout( 2 );
		return $this->request->post(
			'site/check/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Get the latest performance test results.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function results() {
		return $this->request->get(
			'site/result/latest/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Ignore the latest performance test results.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function ignore() {
		return $this->request->get(
			'site/result/ignore/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Test if GZIP is enabled.
	 *
	 * @since 1.6.0
	 * @return array|mixed|object|WP_Error
	 */
	public function check_gzip() {
		$domain = $this->request->get_this_site();

		$params = array(
			'html'       => $domain,
			'javascript' => WPHB_DIR_URL . 'core/modules/dummy/dummy-js.js',
			'css'        => WPHB_DIR_URL . 'core/modules/dummy/dummy-style.css',
		);

		return $this->request->post(
			'test/gzip/',
			array(
				'domain' => $domain,
				'tests'  => wp_json_encode( $params ),
			)
		);
	}

	/**
	 * Test if caching is enabled.
	 *
	 * @since 1.6.0
	 * @return array|mixed|object|WP_Error
	 */
	public function check_cache() {
		$params = array(
			'javascript' => WPHB_DIR_URL . 'core/modules/dummy/dummy-js.js',
			'css'        => WPHB_DIR_URL . 'core/modules/dummy/dummy-style.css',
			'media'      => WPHB_DIR_URL . 'core/modules/dummy/dummy-media.mp3',
			'images'     => WPHB_DIR_URL . 'core/modules/dummy/dummy-image.png',
		);

		return $this->request->post(
			'test/cache/',
			array(
				'domain' => $this->request->get_this_site(),
				'tests'  => wp_json_encode( $params ),
			)
		);
	}

	/**
	 * Set ignore report on server.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function ignore_report() {
		return $this->request->post(
			'site/reports/',
			array(
				'domain' => $this->request->get_this_site(),
				'ignore' => 1,
			)
		);
	}

	/**
	 * Is report ignored.
	 *
	 * @return bool
	 */
	public function is_report_ignored() {
		return $this->request->get(
			'site/reports/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}
}
