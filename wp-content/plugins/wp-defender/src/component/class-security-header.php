<?php
/**
 * Handles security headers.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Model\Setting\Security_Headers;

/**
 * Base class for managing all security headers.
 */
abstract class Security_Header extends Component {

	/**
	 * Static variable to store the rule slug.
	 *
	 * @var string
	 */
	public static $rule_slug;

	/**
	 * Instance of Security_Headers used for caching.
	 *
	 * @var Security_Headers
	 */
	public $model;

	/**
	 * Retrieves miscellaneous data related to the security header.
	 *
	 * @return array Returns an array of miscellaneous data.
	 */
	public function get_misc_data() {
		return array();
	}

	/**
	 * Checks the condition of the security header.
	 *
	 * @return mixed The result of the check.
	 */
	abstract public function check();

	/**
	 * Retrieves the title of the security header.
	 *
	 * @return string The title of the security header.
	 */
	abstract public function get_title();

	/**
	 * Adds necessary hooks related to the security header.
	 *
	 * @return mixed
	 */
	abstract public function add_hooks();

	/**
	 * Safely retrieves the model instance, initializing it if not already done.
	 *
	 * @return Security_Headers The instance of Security_Headers.
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		$this->model = new Security_Headers();
		return $this->model;
	}

	/**
	 * Checks if a specific header is submitted based on the provided conditions.
	 *
	 * @param  string $header  The header to check.
	 * @param  mixed  $somewhere  Additional condition to evaluate.
	 *
	 * @return bool True if the header is submitted, false otherwise.
	 */
	protected function maybe_submit_header( $header, $somewhere ): bool {
		if ( false === $somewhere ) {
			return true;
		}
		$collection = headers_list();
		$match      = false;
		foreach ( $collection as $item ) {
			if ( stristr( $item, $header ) ) {
				$match = true;
			}
		}

		return $match;
	}

	/**
	 * Performs a HEAD request to a URL and caches the response.
	 *
	 * @param  string   $url  The URL to request.
	 * @param  string   $origin  The origin of the request.
	 * @param  int|null $ttl  Time-to-live for the cache entry.
	 *
	 * @return array|mixed The headers from the response or the WP_Error object if the request fails.
	 */
	protected function head_request( $url, $origin, $ttl = null ) {
		$model  = $this->get_model();
		$cached = $model->get_data_values( 'head_requests' );
		if ( ! is_array( $cached ) ) {
			$cached = array();
		}
		if ( isset( $cached[ $url ] ) ) {
			$cache = $cached[ $url ];
			if ( $cache['ttl'] > time() ) {
				return $cache['data'];
			}
		}

		// No cache or cache expired.
		$request = wp_remote_head(
			$url,
			array( 'user-agent' => 'WP Defender self ping - ' . $origin )
		);
		if ( ! is_wp_error( $request ) ) {
			$headers = wp_remote_retrieve_headers( $request );
			$headers = $headers->getAll();
			if ( null === $ttl ) {
				$ttl = strtotime( '+1 day' );
			}
			$headers['response_code'] = wp_remote_retrieve_response_code( $request );
			$cached[ $url ]           = array(
				'ttl'  => apply_filters( 'wd_head_request_ttl', $ttl ),
				'data' => $headers,
			);
			$model->set_data_values( 'head_requests', $cached );
			$this->log( sprintf( 'Fetched header for %s into cache', $url ), wd_internal_log() );

			return $headers;
		}

		return $request;
	}
}