<?php
/**
 * Hummingbird API class.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Api;

use Hummingbird\Core\Api\Service\Cloudflare;
use Hummingbird\Core\Api\Service\Hosting;
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
	 * Uptime API.
	 *
	 * @var Uptime
	 */
	public $uptime;

	/**
	 * Performance API.
	 *
	 * @var Performance
	 */
	public $performance;

	/**
	 * Cloudflare API.
	 *
	 * @var Cloudflare
	 */
	public $cloudflare;

	/**
	 * Minify API.
	 *
	 * @var Minify
	 */
	public $minify;

	/**
	 * Varnish API.
	 *
	 * @var Varnish
	 */
	public $varnish;

	/**
	 * Hub API.
	 *
	 * @var Hub
	 */
	public $hub;

	/**
	 * REST API.
	 *
	 * @var Rest
	 */
	public $rest;

	/**
	 * Hosting/Hub API.
	 *
	 * @since 3.3.1
	 *
	 * @var Hosting
	 */
	public $hosting;

	/**
	 * API constructor.
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

		$this->hosting = new Hosting();
	}

}