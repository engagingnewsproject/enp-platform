<?php
/**
 * Response class
 *
 * @package Calotes\Component
 */

namespace Calotes\Component;

/**
 * Responsible for managing response data.
 */
class Response {

	/**
	 * We will need to have a standard of returning data, frontend will base on the return for handling behavior
	 * Behaviors:
	 *  - message: Show a floating notice using this param as content
	 *  - redirect: Redirect to the URL
	 *  - interval: As second, if this is set, then we will reload the page in period of time, if we have this with
	 * redirect, then, redirect to the URL after period of time Data:
	 *  - data: Contains the data that return to frontend, can be empty
	 * Response constructor.
	 *
	 * @param  boolean $is_success  Indicates if the response is a success or not.
	 * @param  mixed   $data  The data to be sent in the response.
	 */
	public function __construct( $is_success, $data ) {
		$is_success ? wp_send_json_success( $data ) : wp_send_json_error( $data );
	}
}