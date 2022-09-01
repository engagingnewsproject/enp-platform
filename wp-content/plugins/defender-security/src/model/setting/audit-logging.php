<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Audit_Logging extends Setting {
	/**
	 * Option name.
	 * @var string
	 */
	protected $table = 'wd_audit_settings';
	/**
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;
	/**
	 * @defender_property
	 * @var string
	 */
	public $storage_days = '6 months';

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels() {
		return array(
			'enabled'      => __( 'Audit Logging', 'wpdef' ),
			'storage_days' => __( 'Storage for', 'wpdef' ),
		);
	}

	/**
	 * @since 2.6.5
	 * @return bool
	 */
	public function is_active() {

		return (bool) apply_filters( 'wd_audit_enable', $this->enabled );
	}
}
