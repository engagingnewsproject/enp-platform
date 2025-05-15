<?php declare( strict_types=1 );

namespace ACP\Expression\Exception;

use InvalidArgumentException;
use Throwable;

final class OperatorNotFoundException extends InvalidArgumentException {

	public function __construct( string $operator, $code = 0, Throwable $previous = null ) {
		$message = sprintf( 'Operator %s was not found.', $operator );

		parent::__construct( $message, $code, $previous );
	}

}