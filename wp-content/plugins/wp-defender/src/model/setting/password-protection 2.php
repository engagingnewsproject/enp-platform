<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Password_Protection extends Setting {
	public $table = 'wd_password_protection_settings';

	/**
	 * Feature status
	 *
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * @defender_property
	 * @var array
	 */
	public $user_roles = array();

	/**
	 * @defender_property
	 * @var array
	 */
	public $pwned_actions = array();

	/**
	 * @return array
	 */
	public function get_default_values() {

		return array(
			'message' => __( 'You are required to change your password because the password you are using exists on database breach records.', 'wpdef' ),
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
		$this->pwned_actions = array(
			'force_change_message' => $default_values['message'],
		);
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
			'enabled'       => __( 'Pwned Passwords', 'wpdef' ),
			'pwned_actions' => __( 'Force password change', 'wpdef' ),
			'user_roles'    => __( 'User Roles', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * Check if the model is activated.
	 *
	 * @return bool
	 */
	public function is_active() {

		return apply_filters(
			'wd_password_protection_enable',
			$this->enabled && count( $this->user_roles ) > 0
		);
	}
}