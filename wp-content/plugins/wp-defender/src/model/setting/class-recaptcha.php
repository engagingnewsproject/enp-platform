<?php
/**
 * Handle reCaptcha settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for reCaptcha settings.
 */
class Recaptcha extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_recaptcha_settings';

	/**
	 * Feature status.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * Active reCaptcha type.
	 *
	 * @var string
	 * @defender_property
	 * @rule required
	 * @rule in[v2_checkbox,v2_invisible,v3_recaptcha]
	 */
	public $active_type = 'v2_checkbox';

	/**
	 * Data for v2 checkbox reCaptcha.
	 *
	 * @var array
	 * @defender_property
	 */
	public $data_v2_checkbox;

	/**
	 * Data for v2 invisible reCaptcha.
	 *
	 * @var array
	 * @defender_property
	 */
	public $data_v2_invisible;

	/**
	 * Data for v3 reCaptcha.
	 *
	 * @var array
	 * @defender_property
	 */
	public $data_v3_recaptcha;

	/**
	 * Language for reCaptcha.
	 *
	 * @var string
	 * @defender_property
	 * @rule required
	 */
	public $language = '';

	/**
	 * Message for reCaptcha.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $message = '';

	/**
	 * Locations for reCaptcha.
	 *
	 * @var array
	 * @defender_property
	 * @rule required
	 */
	public $locations = array();

	/**
	 * Flag to detect WooCommerce.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $detect_woo = false;

	/**
	 * Checked locations for WooCommerce.
	 *
	 * @var array
	 * @defender_property
	 * @rule required
	 */
	public $woo_checked_locations = array();

	/**
	 * Flag to detect BuddyPress.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $detect_buddypress = false;

	/**
	 * Checked locations for BuddyPress.
	 *
	 * @var array
	 * @defender_property
	 * @rule required
	 */
	public $buddypress_checked_locations = array();

	/**
	 * Flag to disable reCaptcha for known users.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $disable_for_known_users = true;

	/**
	 * Rules for validating the reCaptcha settings.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled', 'detect_woo', 'detect_buddypress' ), 'boolean' ),
		array( array( 'active_type' ), 'in', array( 'v2_checkbox', 'v2_invisible', 'v3_recaptcha' ) ),
	);

	/**
	 * Retrieves the default values for the reCAPTCHA settings.
	 *
	 * @return array An associative array containing the default values.
	 */
	public function get_default_values(): array {
		return array(
			'message' => esc_html__( 'reCAPTCHA verification failed. Please try again.', 'wpdef' ),
		);
	}

	/**
	 *  Load default values.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$default_values          = $this->get_default_values();
		$this->message           = $default_values['message'];
		$this->language          = 'automatic';
		$this->data_v2_checkbox  = array(
			'key'    => '',
			'secret' => '',
			'size'   => 'normal',
			'style'  => 'light',
		);
		$this->data_v2_invisible = array(
			'key'    => '',
			'secret' => '',
		);
		$this->data_v3_recaptcha = array(
			'key'       => '',
			'secret'    => '',
			'threshold' => '0.5',
		);
	}

	/**
	 * Checks if the given reCAPTCHA type is valid and has all the necessary data.
	 *
	 * @param  string $active_type  The reCAPTCHA type to check.
	 *
	 * @return bool Returns true if the reCAPTCHA type is valid and has all the necessary data, false otherwise.
	 */
	private function check_recaptcha_type( string $active_type ): bool {
		if (
			'v2_checkbox' === $active_type
			&& ! empty( $this->data_v2_checkbox['key'] )
			&& ! empty( $this->data_v2_checkbox['secret'] )
		) {
			return true;
		} elseif (
			'v2_invisible' === $active_type
			&& ! empty( $this->data_v2_invisible['key'] )
			&& ! empty( $this->data_v2_invisible['secret'] )
		) {
			return true;
		} elseif (
			'v3_recaptcha' === $active_type
			&& ! empty( $this->data_v3_recaptcha['key'] ) && ! empty( $this->data_v3_recaptcha['secret'] )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the reCAPTCHA is active.
	 *
	 * @return bool Returns true if the reCAPTCHA is active, false otherwise.
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_recaptcha_enable',
			$this->enabled
			&& '' !== $this->active_type
			&& '' !== $this->language
			// For each Recaptcha type.
			&& $this->check_recaptcha_type( $this->active_type )
		);
	}

	/**
	 * Is activated any default location?
	 *
	 * @return bool
	 */
	public function enable_default_location(): bool {
		return ! empty( $this->locations );
	}

	/**
	 * Level#2 check by any activated location. Only if the plugin is enabled.
	 *
	 * @return bool
	 */
	public function enable_woo_location(): bool {
		return $this->detect_woo && ! empty( $this->woo_checked_locations );
	}

	/**
	 * Level#2 check by deactivated locations. Only if the plugin is enabled.
	 *
	 * @return bool
	 */
	public function is_unchecked_woo_locations(): bool {
		return $this->detect_woo && empty( $this->woo_checked_locations );
	}

	/**
	 * Level#1 check. If the plugin is disabled, there is no point further.
	 *
	 * @param  bool $is_woo_activated  Whether WooCommerce is activated or not.
	 *
	 * @return bool
	 */
	public function check_woo_locations( $is_woo_activated ): bool {
		if ( ! $is_woo_activated ) {
			return false;
		}

		return $this->enable_woo_location();
	}

	/**
	 * Level#2 check by any activated location. Only if the plugin is enabled.
	 *
	 * @return bool
	 */
	public function enable_buddypress_location(): bool {
		return $this->detect_buddypress && ! empty( $this->buddypress_checked_locations );
	}

	/**
	 * Level#2 check by deactivated locations. Only if the plugin is enabled.
	 *
	 * @return bool
	 */
	public function is_unchecked_buddypress_locations(): bool {
		return $this->detect_buddypress && empty( $this->buddypress_checked_locations );
	}

	/**
	 * Level#1 Checks if the BuddyPress locations are valid and have all the necessary data.
	 *
	 * @param  bool $is_buddypress_activated  Whether BuddyPress is activated or not.
	 *
	 * @return bool Returns true if the BuddyPress locations are valid and have all the necessary data, false otherwise.
	 */
	public function check_buddypress_locations( $is_buddypress_activated ): bool {
		if ( ! $is_buddypress_activated ) {
			return false;
		}

		return $this->enable_buddypress_location();
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled'                 => self::get_module_name(),
			'active_type'             => esc_html__( 'Configure reCaptcha', 'wpdef' ),
			'v2_checkbox'             => esc_html__( 'V2 Checkbox', 'wpdef' ),
			'v2_invisible'            => esc_html__( 'V2 Invisible', 'wpdef' ),
			'v3_recaptcha'            => esc_html__( 'reCAPTCHA V3', 'wpdef' ),
			'language'                => esc_html__( 'Language', 'wpdef' ),
			'message'                 => esc_html__( 'Error Message', 'wpdef' ),
			'locations'               => esc_html__( 'CAPTCHA Locations', 'wpdef' ),
			'detect_woo'              => esc_html__( 'WooCommerce', 'wpdef' ),
			'detect_buddypress'       => esc_html__( 'BuddyPress', 'wpdef' ),
			'disable_for_known_users' => esc_html__( 'Disable for logged in users', 'wpdef' ),
		);
	}

	/**
	 * Validates the form after submission.
	 *
	 * @return void
	 */
	protected function after_validate(): void {
		// Case with multi errors.
		if ( $this->is_unchecked_woo_locations() && $this->is_unchecked_buddypress_locations() ) {
			// The text of the notation is only in the first key, but we add the number of keys depending on the disabled locations of the plugins.
			$this->errors['enable_woo']        = esc_html__(
				'You have enabled reCaptcha for more than one plugin. Please select at least one form location for each plugin and click Save Changes again.',
				'wpdef'
			);
			$this->errors['enable_buddypress'] = '';
		} else {
			// Individual cases with plugins.
			if ( $this->is_unchecked_woo_locations() ) {
				$this->errors['enable_woo'] = esc_html__(
					'reCAPTCHA for WooCommerce is enabled, but no WooCommerce forms are selected. Please select at least one WooCommerce form location and then click Save Changes again.',
					'wpdef'
				);
			}
			if ( $this->is_unchecked_buddypress_locations() ) {
				$this->errors['enable_buddypress'] = esc_html__(
					'reCAPTCHA for BuddyPress is enabled, but no BuddyPress forms are selected. Please select at least one BuddyPress form location and then click Save Changes again.',
					'wpdef'
				);
			}
		}
	}

	/**
	 * Disable for logged in users or enable.
	 *
	 * @return bool
	 */
	public function display_for_known_users() {
		return ! ( $this->disable_for_known_users && is_user_logged_in() );
	}

	/**
	 * Get module name.
	 *
	 * @return string
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Google reCAPTCHA', 'wpdef' );
	}
}