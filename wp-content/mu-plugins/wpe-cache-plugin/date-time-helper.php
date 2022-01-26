<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

use Exception;

\wpengine\cache_plugin\check_security();

class DateTimeHelper {
	private static $instance;

	public function add_minutes_to_date( string $date, int $num_minutes ) {
		$interval_m = new \DateInterval( 'PT' . $num_minutes . 'M' );
		$this->check_valid_date_string( array( $date ) );
		$date_utc = new \DateTime( $date );

		return $date_utc->add( $interval_m );
	}

	public function now_date_time_utc() {
		return new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
	}

	public function get_later_date( string $date1, string $date2 ) {
		$this->check_valid_date_string( array( $date1, $date2 ) );
		$date1_utc = new \DateTime( $date1 );
		$date2_utc = new \DateTime( $date2 );
		return $date1_utc > $date2_utc ? $date1 : $date2;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new DateTimeHelper();
		}

		return self::$instance;
	}

	private function check_valid_date_string( array $date_array ) {
		foreach ( $date_array as $date ) {
			$date_string = strval( $date );
			if ( ! strtotime( $date_string ) ) {
				throw new Exception( 'Invalid date format:' . $date );
			}
		}
	}
}
