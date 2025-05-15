<?php

namespace ACP\Search\Comparison\Post;

use AC;
use AC\Helper\Select\Options;
use ACP\Search\Comparison;
use ACP\Search\Operators;

class PageTemplate extends Comparison\Meta
	implements Comparison\Values {

	/**
	 * @var array Key is the template name, value is the filename of the template
	 */
	private $templates;

	public function __construct( array $templates ) {
		$operators = new Operators( [
			Operators::EQ,
			Operators::IS_EMPTY,
			Operators::NOT_IS_EMPTY,
		] );

		$this->templates = $templates;

		parent::__construct( $operators, '_wp_page_template' );
	}

	public function get_values(): Options {
		return AC\Helper\Select\Options::create_from_array( array_flip( $this->templates ) );
	}

}