<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Password_Reset extends Setting {
	public $table = 'wd_password_reset_settings';

	/**
	 * @defender_property
	 * @var array
	 */
	public $user_roles = array();
	/**
	 * @defender_property
	 * @var string
	 */
	public $message = '';
	/**
	 * @defender_property
	 * @var bool
	 */
	public $expire_force = false;
	/**
	 * @var int
	 * @defender_property
	 */
	public $force_time;

	/**
	 * @return array
	 */
	public function get_default_values() {

		return array(
			'message' => __( 'You are required to change your password to a new one to use this site.', 'wpdef' ),
		);
	}

	protected function before_load() {
		$default_values = $this->get_default_values();
		// Default we will load all rules.
		if ( function_exists( 'get_editable_roles' ) ) {
			// We only need this inside admin, no need to load the user.php everywhere.
			$this->user_roles = array_keys( get_editable_roles() );
		} else {
			// Define defaults user roles.
			$this->user_roles = array( 'administrator' );
		}
		$this->message = $default_values['message'];
	}

	/**
	 * Define labels for settings key.
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'user_roles' => __( 'User Roles', 'wpdef' ),
			'message'    => __( 'Message', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * Checks for active feature:
	 * check 'expire_force', check one role at least, there is a reset time.
	 *
	 * @return bool
	 */
	public function is_active() {

		return apply_filters(
			'wd_password_reset_active',
			$this->expire_force && count( $this->user_roles ) > 0 && $this->force_time
		);
	}
}