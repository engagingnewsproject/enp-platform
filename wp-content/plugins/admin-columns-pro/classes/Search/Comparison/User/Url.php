<?php

namespace ACP\Search\Comparison\User;

use ACP\Search\Operators;

class Url extends UserField {

	public function __construct() {
		$operators = new Operators( [
			Operators::EQ,
			Operators::CONTAINS,
			Operators::NOT_CONTAINS,
			Operators::BEGINS_WITH,
			Operators::ENDS_WITH,
		] );

		parent::__construct( $operators );
	}

	protected function get_field(): string
    {
		return 'user_url';
	}

}