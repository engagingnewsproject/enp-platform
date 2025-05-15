<?php

namespace ACP\Column\NetworkSite;

use AC;

class Registered extends AC\Column {

	public function __construct() {
		$this->set_type( 'registered' )
		     ->set_original( true );
	}

	public function register_settings() {
		$this->get_setting( 'width' )->set_default( 20 );
	}

}