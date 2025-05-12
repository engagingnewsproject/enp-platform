<?php
/**
 * Handles the scheduling of cron jobs within the WordPress environment.
 *
 * @package    WP_Defender\Component\Scheduler
 */

namespace WP_Defender\Component\Scheduler;

/**
 * Service layer to handle cron schedule.
 */
class Scheduler {

	/**
	 * Filter array cron schedules with matched cron keys.
	 *
	 * @param  array $cron_keys  Array of cron keys to fetch respective cron schedules.
	 *
	 * @return array Array of cron schedules.
	 */
	public function filter_cron_schedules( array $cron_keys ): array {
		return array_filter(
			wp_get_schedules(),
			function ( $v, $k ) use ( $cron_keys ): bool {
				return in_array(
					$k,
					$cron_keys,
					true
				);
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Override existing schedule with new schedule.
	 *
	 * @param  string $hook  Schedule hook tag to execute/trigger.
	 * @param  string $recurrence  Schedule name.
	 */
	public function override_schedule( string $hook, string $recurrence ): void {
		$timestamp = (int) wp_next_scheduled( $hook );

		wp_clear_scheduled_hook( $hook );

		wp_reschedule_event( $timestamp, $recurrence, $hook );
	}

	/**
	 * Get cron schedule interval.
	 *
	 * @param string $schedule Schedule name.
	 *
	 * @since 5.1.1
	 * @return int|bool
	 */
	public function get_cron_schedule_interval( $schedule ) {
		$schedules = wp_get_schedules();
		return isset( $schedules[ $schedule ] ) ? $schedules[ $schedule ]['interval'] : false;
	}
}