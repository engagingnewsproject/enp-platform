<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Firewall extends Setting {
	/**
	 * Option name
	 * @var string
	 */
	protected $table = 'wd_lockdown_settings';

	/**
	 * @var string
	 * @defender_property
	 */
	public $ip_blocklist_cleanup_interval = 'never';

	/**
	 * @var int
	 * @defender_property
	 */
	public $storage_days = 30;

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'storage_days'                  => __( 'Days to keep logs', 'wpdef' ),
			'ip_blocklist_cleanup_interval' => __( 'Clear Temporary IP Block List', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}