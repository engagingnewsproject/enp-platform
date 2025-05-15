<?php

namespace ACA\WC\Search\Product;

use AC;
use AC\Helper\Select\Options;
use ACP\Search\Comparison;
use ACP\Search\Operators;

class TaxStatus extends Comparison\Meta implements Comparison\Values {

	/**
	 * @var array
	 */
	private $statuses;

	public function __construct( array $statuses ) {
		$operators = new Operators( [
			Operators::EQ,
			Operators::NEQ,
			Operators::IS_EMPTY,
			Operators::NOT_IS_EMPTY,
		] );

		$this->statuses = $statuses;

		parent::__construct( $operators, '_tax_status' );
	}

	public function get_values(): Options {
		return AC\Helper\Select\Options::create_from_array( $this->statuses );
	}

}