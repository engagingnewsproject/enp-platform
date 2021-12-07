<?php

namespace WP_Defender\Component\Audit;

use Calotes\Base\Component;
use WP_Defender\Traits\Formats;

class Audit_Time extends Component {
	use Formats;

	/**
	 * Get report time as string, we will use this in summary box.
	 *
	 * @return string
	 * @deprecated
	 */
	public function get_report_times_as_string() {
		$settings    = new \WP_Defender\Model\Setting\Audit_Logging();
		$report_time = '-';
		if ( true === $settings->notification ) {
			if ( 1 === (int) $settings->frequency ) {
				$report_time = sprintf(
				/* translators: */
					__( 'at %s', 'wpdef' ),
					strftime( '%I:%M %p', strtotime( $settings->time ) )
				);
			} else {
				$component   = new \WP_Defender\Component();
				$report_time = sprintf(
				/* translators: */
					__( '%1$s on %2$s at %3$s', 'wpdef' ),
					ucfirst( $component->frequency_to_text( $settings->frequency ) ),
					ucfirst( $settings->day ),
					strftime( '%I:%M %p', strtotime( $settings->time ) )
				);

			}
		}

		return $report_time;
	}
}
