<?php
/**
 * Handles scan settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use DateTime;
use Exception;
use Calotes\Model\Setting;
use function wp_timezone;

/**
 * Model for scan settings.
 */
class Scan extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_scan_settings';

	/**
	 * Enable core/plugin integrity check while perform a scan.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $integrity_check = true;

	/**
	 * Enable Scan WP core files.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $check_core = true;

	/**
	 * Enable Scan plugin files.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $check_plugins = true;

	/**
	 * Check the files inside wp-content by our malware signatures.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $scan_malware = true;

	/**
	 * Check if any plugins or themes have a known vulnerability.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $check_known_vuln = true;

	/**
	 * If a file is smaller than this, we wil include it to the test.
	 *
	 * @defender_property
	 * @var int
	 */
	public $filesize = 10;

	/**
	 * Is scheduled scanning enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $scheduled_scanning = false;

	/**
	 * The frequency of scheduled scan.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[daily,weekly,monthly]
	 */
	public $frequency;

	/**
	 * The day of scheduled scan.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $day;

	/**
	 * This is for when user select scheduled scan as monthly, we will have the day number, instead of text.
	 *
	 * @var int
	 * @sanitize_text_field
	 * @defender_property
	 */
	public $day_n;

	/**
	 * Same as $day.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $time;

	/**
	 * Quarantine file deletion/expiration cron schedule.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $quarantine_expire_schedule = 'thirty_days';

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'integrity_check'    => esc_html__( 'File change detection', 'wpdef' ),
			'check_core'         => esc_html__( 'Scan core files', 'wpdef' ),
			'check_plugins'      => esc_html__( 'Scan plugin files', 'wpdef' ),
			'check_known_vuln'   => esc_html__( 'Known vulnerabilities', 'wpdef' ),
			'scan_malware'       => esc_html__( 'Suspicious Code', 'wpdef' ),
			'filesize'           => esc_html__( 'Max included file size', 'wpdef' ),
			'scheduled_scanning' => esc_html__( 'Scheduled Scanning', 'wpdef' ),
			'frequency'          => esc_html__( 'Frequency', 'wpdef' ),
			'day'                => esc_html__( 'Day of the week', 'wpdef' ),
			'day_n'              => esc_html__( 'Day of the month', 'wpdef' ),
			'time'               => esc_html__( 'Time of day', 'wpdef' ),
		);
	}

	/**
	 * Check different cases for 'File change detection' option.
	 *
	 * @return bool
	 */
	public function is_checked_any_file_change_types(): bool {
		if ( ! $this->integrity_check ) {
			// Check the parent type.
			return false;
		} elseif ( $this->integrity_check && ! $this->check_core && ! $this->check_plugins ) {
			// Check the parent and child types.
			return false;
		}

		return true;
	}

	/**
	 * Validates the input after form submission and adds error messages if necessary.
	 *
	 * @return void
	 */
	protected function after_validate(): void {
		// Case#1: all child types of File change detection are unchecked BUT parent type is checked.
		if ( $this->integrity_check && ! $this->check_core && ! $this->check_plugins ) {
			$this->errors[] = sprintf(
				/* translators: %s: File change detection. */
				esc_html__( 'You have not selected a scan type for the %s. Please choose at least one and save the settings again.', 'wpdef' ),
				'<strong>' . esc_html__( 'File change detection', 'wpdef' ) . '</strong>'
			);
			// Case#2: all scan types are unchecked and Scheduled Scanning is checked.
		} elseif ( ! $this->integrity_check && ! $this->check_known_vuln && ! $this->scan_malware
					&& $this->scheduled_scanning
		) {
			$this->errors[] = esc_html__(
				'You have not selected a scan type. Please enable at least one scan type and save the settings again.',
				'wpdef'
			);
		}
	}

	/**
	 * Initializes the object by setting default values for the frequency, day, day_n, and time properties based on the
	 * current day and time.
	 *
	 * @return void
	 * @throws Exception Emits Exception in case of an error.
	 * @since 4.7.1 Implement Dynamic Scan Scheduling to avoid event spikes on MP.
	 */
	protected function before_load(): void {
		// Get current day and time.
		$date          = new DateTime( 'now', wp_timezone() );
		$result        = explode( '--', $date->format( 'l--H--i' ) );
		$day           = strtolower( $result[0] );
		$current_hours = (int) $result[1];
		$current_mins  = (int) $result[2];
		// We have a 30 minute span, so XX:00-15 => XX:00, XX:16-45 => XX:30, XX:46-59 => (XX+1):00.
		if ( $current_mins > 15 && $current_mins <= 45 ) {
			$mins = '30';
		} elseif ( $current_mins >= 0 && $current_mins < 16 ) {
			$mins = '00';
		} else {
			++$current_hours;
			$current_hours >= 24 ? '00' : $current_hours;
			$mins = '00';
		}

		$this->frequency = 'weekly';
		$this->day       = $day;
		$this->day_n     = '1';
		$this->time      = $current_hours . ':' . $mins;
	}
}