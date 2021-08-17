<?php
/**
 * Abstract service class.
 *
 * @package Hummingbird.
 */

namespace Hummingbird\Core\Api\Service;

use Hummingbird\Core\Api\Request\Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Service
 *
 * @package Hummingbird\Core\Api\Service
 */
abstract class Service {

	/**
	 * Service name.
	 *
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Request.
	 *
	 * @var null|Request
	 */
	protected $request = null;

	/**
	 * Get the Service Name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

}
