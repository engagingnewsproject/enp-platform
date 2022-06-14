<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Model\Setting\Security_Headers;

abstract class Security_Header extends Component {

	/**
	 * @var string
	 */
	static $rule_slug;

	/**
	 * Use for cache.
	 *
	 * @var Security_Headers
	 */
	public $model;

	/**
	 * @return array
	 */
	public function get_misc_data() {
		return array();
	}

	/**
	 * @return mixed
	 */
	abstract function check();

	/**
	 * @return string
	 */
	abstract function get_title();

	/**
	 * @return mixed
	 */
	abstract function add_hooks();

	/**
	 * Safe way to get cached model.
	 *
	 * @return Security_Headers
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return $this->model = new Security_Headers();
	}

	/**
	 * Check if the header is out or not.
	 *
	 * @param $header
	 * @param $somewhere
	 *
	 * @return bool
	 */
	protected function maybe_submit_header( $header, $somewhere ) {
		if ( false === $somewhere ) {
			return true;
		}
		$list  = headers_list();
		$match = false;
		foreach ( $list as $item ) {
			if ( stristr( $item, $header ) ) {
				$match = true;
			}
		}

		return $match;
	}


	/**
	 * @param $url
	 * @param $origin
	 * @param $ttl
	 *
	 * @return array|mixed
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
			array(
				'user-agent' => 'WP Defender self ping - ' . $origin,
			)
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
			$this->log( sprintf( 'Fetched header for %s into cache', $url ) );

			return $headers;
		}

		return $request;
	}
}
