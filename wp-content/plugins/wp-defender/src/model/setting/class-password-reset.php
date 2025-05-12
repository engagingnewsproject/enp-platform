<?php
/**
 * Handle password reset settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for password reset settings.
 */
class Password_Reset extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_password_reset_settings';

	/**
	 * List of user roles.
	 *
	 * @var array
	 * @defender_property
	 */
	public $user_roles = array();

	/**
	 * Message for password reset.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $message = '';

	/**
	 * Force password reset expired?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $expire_force = false;

	/**
	 * Force password reset time.
	 *
	 * @var int
	 * @defender_property
	 */
	public $force_time;

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public function get_default_values(): array {
		return array(
			'message' => esc_html__(
				'You are required to change your password to a new one to use this site.',
				'wpdef'
			),
		);
	}

	/**
	 * Loads default values and sets the user roles based on the availability of the `get_editable_roles` function.
	 *
	 * @return void
	 */
	protected function before_load(): void {
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
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'user_roles' => esc_html__( 'User Roles', 'wpdef' ),
			'message'    => esc_html__( 'Message', 'wpdef' ),
		);
	}

	/**
	 * Checks for active feature: 'expire_force', one role at least and there is a reset time.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_password_reset_active',
			$this->expire_force && count( $this->user_roles ) > 0 && $this->force_time
		);
	}

	/**
	 * Returns the module name for Password Reset.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Password Reset', 'wpdef' );
	}
}