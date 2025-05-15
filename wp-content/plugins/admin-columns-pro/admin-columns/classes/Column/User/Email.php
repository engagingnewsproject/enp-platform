<?php

namespace AC\Column\User;

use AC\Column;

/**
 * @since 3.0
 */
class Email extends Column {

	public function __construct() {
		$this->set_original( true );
		$this->set_type( 'email' );
	}

}