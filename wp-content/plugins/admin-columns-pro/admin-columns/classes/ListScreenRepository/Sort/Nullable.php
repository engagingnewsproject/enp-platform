<?php

namespace AC\ListScreenRepository\Sort;

use AC\ListScreenCollection;
use AC\ListScreenRepository\Sort;

class Nullable implements Sort {

	public function sort( ListScreenCollection $list_screens ): ListScreenCollection {
		return $list_screens;
	}

}