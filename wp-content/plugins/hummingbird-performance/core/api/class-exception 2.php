<?php
/**
 * Exception class.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Exception
 *
 * @package Hummingbird\Core\Api
 */
class Exception extends \Exception {

	/**
	 * Exception constructor.
	 *
	 * @param string         $message   Error message.
	 * @param int            $code      Error code.
	 * @param Exception|null $previous  Previous exception.
	 */
	public function __construct( $message = '', $code = 0, Exception $previous = null ) {
		if ( ! is_numeric( $code ) ) {
			switch ( $code ) {
				default:
					$code = 500;
			}
		}

		parent::__construct( $message, $code );
	}
}
