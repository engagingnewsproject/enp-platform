<?php
/**
 * Handles time related tasks.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Calotes\Base\Component;

/**
 * Handles time related tasks.
 */
class Timer extends Component {

	/**
	 *  The start time of the timer.
	 *
	 * @var int
	 */
	protected $clock;

	/**
	 * Constructor initializes and starts the timer.
	 */
	public function __construct() {
		$this->start();
	}

	/**
	 * Retrieves the maximum execution time allowed for scripts, halved.
	 *
	 * @return int The half of the maximum execution time in seconds.
	 */
	public function get_max_time() {
		$max = ini_get( 'max_execution_time' );
		if ( ! filter_var( $max, FILTER_VALIDATE_INT ) ) {
			$max = 30;
		}

		return $max / 2;
	}

	/**
	 * Starts or restarts the timer.
	 *
	 * @return void
	 */
	public function start(): void {
		$this->clock = time();
	}

	/**
	 * Checks if the elapsed time has exceeded half of the maximum execution time.
	 *
	 * @return bool True if the current elapsed time is less than half of the max execution time, false otherwise.
	 */
	public function check(): bool {
		if ( ( $this->get_difference() / 1000 ) >= $this->get_max_time() ) {
			return false;
		}

		return true;
	}

	/**
	 * Calculates the difference in seconds from when the timer was started to the current time.
	 *
	 * @return int The time difference in seconds.
	 */
	public function get_difference(): int {
		return time() - $this->clock;
	}
}