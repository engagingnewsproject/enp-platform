<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Scan extends Setting {
	public $table = 'wd_scan_settings';

	/**
	 * Enable core/theme/plugin integrity check while perform a scan
	 * @defender_property
	 * @var bool
	 */
	public $integrity_check = true;

	/**
	 * Enable Scan WP core files
	 * @defender_property
	 * @var bool
	 */
	public $check_core = true;

	/**
	 * Enable Scan plugin files
	 * @defender_property
	 * @var bool
	 */
	public $check_plugins = false;

	/**
	 * Check the files inside wp-content by our malware signatures
	 * @defender_property
	 * @var bool
	 */
	public $scan_malware = false;

	/**
	 * Check if any plugins or themes have a known vulnerability
	 * @defender_property
	 * @var bool
	 */
	public $check_known_vuln = true;

	/**
	 * If a file is smaller than this, we wil include it to the test
	 * @defender_property
	 * @var int
	 */
	public $filesize = 10;

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'integrity_check'  => __( 'File change detection', 'wpdef' ),
			'check_core'       => __( 'Scan core files', 'wpdef' ),
			'check_plugins'    => __( 'Scan plugin files', 'wpdef' ),
			'check_known_vuln' => __( 'Known vulnerabilities', 'wpdef' ),
			'scan_malware'     => __( 'Suspicious Code', 'wpdef' ),
			'filesize'         => __( 'Max included file size', 'wpdef' ),
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
		if ( $this->integrity_check && ! $this->check_core && ! $this->check_plugins ) {
			$this->errors[] = __( 'You have not selected a scan type for the <strong>File change detection</strong>. Please choose at least one and save the settings again.', 'wpdef' );

			return false;
		}
	}
}
