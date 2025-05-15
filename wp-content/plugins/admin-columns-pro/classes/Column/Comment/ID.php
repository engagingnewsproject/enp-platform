<?php

namespace ACP\Column\Comment;

use AC;
use ACP\ConditionalFormat;
use ACP\Search;
use ACP\Sorting;

/**
 * @since 2.0
 */
class ID extends AC\Column\Comment\ID
	implements Sorting\Sortable, Search\Searchable, ConditionalFormat\Formattable {

	use ConditionalFormat\IntegerFormattableTrait;

	public function sorting() {
		return new Sorting\Model\OrderBy( 'comment_ID' );
	}

	public function search() {
		return new Search\Comparison\Comment\ID();
	}

}