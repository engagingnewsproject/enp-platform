<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Scan extends Setting {
	public $table = 'wd_scan_settings';

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
	public $check_plugins = false;

	/**
	 * Check the files inside wp-content by our malware signatures.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $scan_malware = false;

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
	 * @defender_property
	 * @var bool
	 */
	public $scheduled_scanning = false;

	/**
	 * The frequency of scheduled scan.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
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
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'integrity_check'    => __( 'File change detection', 'wpdef' ),
			'check_core'         => __( 'Scan core files', 'wpdef' ),
			'check_plugins'      => __( 'Scan plugin files', 'wpdef' ),
			'check_known_vuln'   => __( 'Known vulnerabilities', 'wpdef' ),
			'scan_malware'       => __( 'Suspicious Code', 'wpdef' ),
			'filesize'           => __( 'Max included file size', 'wpdef' ),
			'scheduled_scanning' => __( 'Scheduled Scanning', 'wpdef' ),
			'frequency'          => __( 'Frequency', 'wpdef' ),
			'day'                => __( 'Day of the week', 'wpdef' ),
			'day_n'              => __( 'Day of the month', 'wpdef' ),
			'time'               => __( 'Time of day', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * @return bool
	 */
	public function is_any_filetypes_checked() {
		if ( ! $this->integrity_check) {
			return false;
		} elseif ( $this->integrity_check && ! $this->check_core && ! $this->check_plugins ) {
			return false;
		}

		return true;
	}

	public function after_validate() {
		// Case#1: all child types of File change detection are unchecked BUT parent type is checked.
		if ( $this->integrity_check && ! $this->check_core && ! $this->check_plugins ) {
			$this->errors[] = __( 'You have not selected a scan type for the <strong>File change detection</strong>. Please choose at least one and save the settings again.', 'wpdef' );

			return false;
		// Case#2: all scan types are unchecked and Scheduled Scanning is checked.
		} elseif ( ! $this->integrity_check && ! $this->check_known_vuln && ! $this->scan_malware
			&& $this->scheduled_scanning
		) {
			$this->errors[] = __( 'You have not selected a scan type. Please enable at least one scan type and save the settings again.', 'wpdef' );

			return false;
		}
	}

	protected function before_load() {
		$this->frequency = 'weekly';
		$this->day       = 'sunday';
		$this->day_n     = '1';
		$this->time      = '4:00';
	}
}
