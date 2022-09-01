<?php
declare( strict_types=1 );

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Password_Reset extends Setting {
	public $table = 'wd_password_reset_settings';

	/**
	 * @defender_property
	 * @var array
	 */
	public $user_roles = [];

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
	public function get_default_values(): array {
		return [
			'message' => __( 'You are required to change your password to a new one to use this site.', 'wpdef' ),
		];
	}

	protected function before_load(): void {
		$default_values = $this->get_default_values();
		// Default we will load all rules.
		if ( function_exists( 'get_editable_roles' ) ) {
			// We only need this inside admin, no need to load the user.php everywhere.
			$this->user_roles = array_keys( get_editable_roles() );
		} else {
			// Define defaults user roles.
			$this->user_roles = [ 'administrator' ];
		}
		$this->message = $default_values['message'];
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return [
			'user_roles' => __( 'User Roles', 'wpdef' ),
			'message' => __( 'Message', 'wpdef' ),
		];
	}

	/**
	 * Checks for active feature:
	 * check 'expire_force', check one role at least, there is a reset time.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_password_reset_active',
			$this->expire_force && count( $this->user_roles ) > 0 && $this->force_time
		);
	}
}
