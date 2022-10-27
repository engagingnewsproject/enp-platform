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
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels() {
		return array(
			'storage_days'                  => __( 'Days to keep logs', 'wpdef' ),
			'ip_blocklist_cleanup_interval' => __( 'Clear Temporary IP Block List', 'wpdef' ),
		);
	}
}
