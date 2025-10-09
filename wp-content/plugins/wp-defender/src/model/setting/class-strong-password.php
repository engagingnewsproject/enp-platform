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
	 * Plugin integrations settings.
	 *
	 * @defender_property
	 * @var array
	 */
	public $plugins = array();

	/**
	 * Form integrations settings.
	 *
	 * @defender_property
	 * @var array
	 */
	public $forms = array();

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
		// Only set defaults if not already configured.
		if ( 0 === count( $this->plugins ) ) {
			$this->plugins = $this->get_default_plugins();
		}
		if ( 0 === count( $this->forms ) ) {
			$this->forms = $this->get_default_forms();
		}
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
		if ( '' === $this->message ) {
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

	/**
	 * Get default plugin integrations.
	 */
	protected function get_default_plugins(): array {
		return array(
			'woocommerce' => false,
		);
	}

	/**
	 * Get default form integrations.
	 */
	protected function get_default_forms(): array {
		return array(
			'woocommerce' => array(),
		);
	}

	/**
	 * Check by deactivated locations. Only if the plugin is enabled.
	 *
	 * @return bool
	 */
	public function is_unchecked_woo_locations(): bool {
		return isset( $this->plugins['woocommerce'] ) && $this->plugins['woocommerce'] && array() === $this->forms['woocommerce'];
	}

	/**
	 * Validates the form after submission.
	 *
	 * @return void
	 */
	protected function after_validate(): void {
		if ( $this->is_unchecked_woo_locations() ) {
			$this->errors['enable_woo'] = esc_html__(
				"You have enabled Strong password rule for WooCommerce but you've not selected any form yet. Please select at least one form to proceed.",
				'wpdef'
			);
		}
	}

	/**
	 * Export model data with proper type casting.
	 *
	 * @return array
	 */
	public function export(): array {
		$data = parent::export();

		// Cast plugin booleans.
		if ( isset( $data['plugins']['woocommerce'] ) ) {
			$data['plugins']['woocommerce'] = (bool) $data['plugins']['woocommerce'];
		}

		return $data;
	}
}