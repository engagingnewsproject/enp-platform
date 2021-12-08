<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;

class Timer extends Component {
	protected $clock;

	public function __construct() {
		$this->start();
	}

	public function get_max_time() {
		$max = ini_get( 'max_execution_time' );
		if ( ! filter_var( $max, FILTER_VALIDATE_INT ) ) {
			$max = 30;
		}

		return $max / 2;
	}

	public function start() {
		$this->clock = time();
	}

	/**
	 * @return bool
	 */
	public function check() {
		$eslaped = time() - $this->clock;
		if ( ( $eslaped / 1000 ) >= $this->get_max_time() ) {
			return false;
		}

		return true;
	}
}