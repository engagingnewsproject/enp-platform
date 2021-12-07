<?php
/**
 * Hummingbird API class.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Api;

use Hummingbird\Core\Api\Service\Cloudflare;
use Hummingbird\Core\Api\Service\Minify;
use Hummingbird\Core\Api\Service\Performance;
use Hummingbird\Core\Api\Service\Uptime;
use Hummingbird\Core\Api\Service\Varnish;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class API
 */
class API {

	/**
	 * API constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		$this->uptime      = new Uptime();
		$this->performance = new Performance();
		$this->cloudflare  = new Cloudflare();
		$this->minify      = new Minify();
		$this->varnish     = new Varnish();

		// Init Hub endpoints.
		$this->hub = new Hub();

		// Init REST endpoints.
		$this->rest = new Rest();
	}

}
