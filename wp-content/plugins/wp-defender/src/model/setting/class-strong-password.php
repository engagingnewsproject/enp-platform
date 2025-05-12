<?php
/**
 * Handle strong password module.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for strong password module.
 */
class Strong_Password extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_strong_password_settings';

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
	 * Default message.
	 *
	 * @defender_property
	 * @var string
	 */
	public $message = '';

	/**
	 * Check if the model is activated.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_strong_password_enable',
			$this->enabled && count( $this->user_roles ) > 0
		);
	}

	/**
	 * Initializes the object before loading.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$this->user_roles = $this->get_default_roles();
		$this->message    = $this->get_message();
	}

	/**
	 * Get applicable user roles.
	 */
	protected function get_default_roles(): array {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			return array( 'administrator' );
		}

		return array_keys( get_editable_roles() );
	}

	/**
	 * Get error message to show in ui.
	 *
	 * @return string
	 */
	public function get_message(): string {
		if ( empty( $this->message ) ) {
			return $this->get_default_message();
		}
		return $this->message;
	}

	/**
	 * Get localized default message.
	 */
	protected function get_default_message(): string {
		return __(
			"You are required to change your password because your password doesn't meet the strong password guidelines set by the administrator.",
			'wpdef'
		);
	}
}