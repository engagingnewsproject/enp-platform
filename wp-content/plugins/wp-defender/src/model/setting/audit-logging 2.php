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
	 * Define labels for settings key.
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'enabled'      => __( 'Audit Logging', 'wpdef' ),
			'storage_days' => __( 'Storage for', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * @since 2.6.5
	 * @return bool
	 */
	public function is_active() {

		return (bool) apply_filters( 'wd_audit_enable', $this->enabled );
	}
}