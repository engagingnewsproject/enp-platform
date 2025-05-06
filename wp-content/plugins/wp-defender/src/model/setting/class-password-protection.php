<?php
/**
 * Handle password protection settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for password protection settings.
 */
class Password_Protection extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_password_protection_settings';

	/**
	 * Feature status
	 *
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * List of user roles.
	 *
	 * @defender_property
	 * @var array
	 */
	public $user_roles = array();

	/**
	 * List of pwned actions.
	 *
	 * @defender_property
	 * @var array
	 */
	public $pwned_actions = array();

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public function get_default_values(): array {
		return array(
			'message' => esc_html__(
				'You are required to change your password because the password you are using exists on database breach records.',
				'wpdef'
			),
		);
	}

	/**
	 * Initializes the object before loading.
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
		$this->pwned_actions = array(
			'force_change_message' => $default_values['message'],
		);
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled'       => self::get_module_name(),
			'pwned_actions' => esc_html__( 'Force password change', 'wpdef' ),
			'user_roles'    => esc_html__( 'User Roles', 'wpdef' ),
		);
	}

	/**
	 * Check if the model is activated.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_password_protection_enable',
			$this->enabled && count( $this->user_roles ) > 0
		);
	}

	/**
	 * Returns the module name for Pwned Passwords.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Pwned Passwords', 'wpdef' );
	}
}