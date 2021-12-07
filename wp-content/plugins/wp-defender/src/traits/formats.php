<?php

namespace WP_Defender\Traits;

trait Formats {
	/**
	 * Convert a unixtimestamp into the blog datetime
	 *
	 * @param $timestamp
	 * @param  bool  $i18n
	 *
	 * @return false|string
	 */
	public function format_date_time( $timestamp, $i18n = true ) {
		if ( ! filter_var( $timestamp, FILTER_VALIDATE_INT ) ) {
			$timestamp = strtotime( $timestamp );
		}
		if ( false === $timestamp ) {
			return 'n/a';
		}
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		if ( false === $i18n ) {
			return gmdate( $format, $timestamp );
		}
		$time = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' );

		return date_i18n( $format, strtotime( $time ) );
	}

	/**
	 * Persistent date & time formats for Hub data
	 *
	 * @param $timestamp
	 *
	 * @return string
	 */
	public function persistent_hub_datetime_format( $timestamp ) {
		return gmdate( 'Y-m-d g:i a', $timestamp );
	}

	/**
	 * @param $bytes
	 *
	 * @return string
	 */
	public function format_bytes_into_readable( $bytes ) {
		if ( $bytes >= 1073741824 ) {
			$bytes = number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			$bytes = number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			$bytes = number_format( $bytes / 1024, 2 ) . ' KB';
		} elseif ( $bytes > 1 ) {
			$bytes = $bytes . ' bytes';
		} elseif ( 1 === $bytes ) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0 bytes';
		}

		return $bytes;
	}

	/**
	 * @param $since
	 *
	 * @return string
	 */
	public function time_since( $since ) {
		$since = time() - $since;
		if ( $since < 0 ) {
			$since = 0;
		}
		$chunks = array(
			array( 60 * 60 * 24 * 365, esc_html__( 'year', 'wpdef' ) ),
			array( 60 * 60 * 24 * 30, esc_html__( 'month', 'wpdef' ) ),
			array( 60 * 60 * 24 * 7, esc_html__( 'week', 'wpdef' ) ),
			array( 60 * 60 * 24, esc_html__( 'day' ) ),
			array( 60 * 60, esc_html__( 'hour', 'wpdef' ) ),
			array( 60, esc_html__( 'minute', 'wpdef' ) ),
			array( 1, esc_html__( 'second', 'wpdef' ) ),
		);

		for ( $i = 0, $j = count( $chunks ); $i < $j; $i ++ ) {
			$seconds = $chunks[ $i ][0];
			$name    = $chunks[ $i ][1];
			$count   = floor( $since / $seconds );
			if ( 0 !== $count ) {
				break;
			}
		}

		$print = ( 1 === $count ) ? '1 ' . $name : "$count {$name}s";

		return $print;
	}

	/**
	 * Get formatted date
	 *
	 * @param $date
	 *
	 * @return string
	 */
	public function get_date( $date ) {
		if ( strtotime( '-24 hours' ) > $date ) {
			return $this->format_date_time( gmdate( 'Y-m-d H:i:s', $date ) );
		} else {
			return human_time_diff( $date, time() ) . ' ' . __( 'ago', 'wpdef' ); // phpcs:ignore
		}
	}

	/**
	 * Return times frame for selectbox
	 *
	 * @return array
	 */
	public function get_times() {
		$times_interval = apply_filters( 'defender_get_times_interval', array( '00', '30' ) );
		$data           = array();
		for ( $i = 0; $i < 24; $i ++ ) {
			foreach ( $times_interval as $min ) {
				$time          = $i . ':' . $min;
				$data[ $time ] = date_i18n( 'h:i A', strtotime( $time ) );
			}
		}

		return $data;
	}

	/**
	 * @param  string  $timestring
	 *
	 * @return false|int
	 * @throws \Exception
	 */
	public function local_to_utc( $timestring ) {
		$tz = get_option( 'timezone_string' );
		if ( ! $tz ) {
			$gmt_offset = get_option( 'gmt_offset' );
			if ( 0 === $gmt_offset ) {
				return strtotime( $timestring );
			}
			$tz = $this->get_timezone_string( $gmt_offset );
		}
		if ( ! $tz ) {
			$tz = 'UTC';
		}
		$timezone = new \DateTimeZone( $tz );
		$time     = new \DateTime( $timestring, $timezone );

		return $time->getTimestamp();
	}

	/**
	 * @param  string  $timezone
	 *
	 * @return false|string
	 */
	public function get_timezone_string( $timezone ) {
		$timezone = explode( '.', $timezone );
		if ( isset( $timezone[1] ) ) {
			$timezone[1] = 30;
		} else {
			$timezone[1] = '00';
		}
		$offset = implode( ':', $timezone );

		list( $hours, $minutes ) = explode( ':', $offset );
		$seconds = $hours * 60 * 60 + $minutes * 60;
		$lc      = localtime( time(), true );
		if ( isset( $lc['tm_isdst'] ) ) {
			$isdst = $lc['tm_isdst'];
		} else {
			$isdst = 0;
		}
		$tz = timezone_name_from_abbr( '', $seconds, $isdst );
		if ( false === $tz ) {
			$tz = timezone_name_from_abbr( '', $seconds, 0 );
		}

		return $tz;
	}

	/**
	 * Get days of week
	 *
	 * @return mixed|void
	 */
	public function get_days_of_week() {
		$timestamp = strtotime( 'next Sunday' );
		$days      = array();
		for ( $i = 0; $i < 7; $i ++ ) {
			$days[ strtolower( date( 'l', $timestamp ) ) ] = date_i18n( 'l', $timestamp );
			$timestamp                                     = strtotime( '+1 day', $timestamp );
		}

		return $days;
	}

	/**
	 * We translate the datetime format from php to something that momentjs can understand
	 *
	 * @param $format
	 *
	 * @return string
	 */
	public function moment_datetime_format_from( $format ) {
		$replacements = [
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X',
		];

		return strtr( $format, $replacements );
	}

	/**
	 * This will calculate the date interval time
	 * @param string $date
	 *
	 * @return string $time
	 */
	public function calculate_date_interval( $date ) {
		$interval = '';
		if ( '24 hours' == $date ) {
			$interval = 'P1D';
		} else if ( '7 days' == $date ) {
			$interval = 'P7D';
		} else if ( '30 days' == $date ) {
			$interval = 'P30D';
		} else if ( '3 months' == $date ) {
			$interval = 'P3M';
		} else if ( '6 months' == $date ) {
			$interval = 'P6M';
		} else if ( '12 months' == $date ) {
			$interval = 'P12M';
		}

		return $interval;
	}
}