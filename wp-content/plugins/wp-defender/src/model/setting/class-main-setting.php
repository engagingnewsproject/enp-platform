<?php
/**
 * Handles the main settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for main settings.
 */
class Main_Setting extends Setting {

	public const PRIVACY_LINK = 'https://wpmudev.com/docs/privacy/our-plugins/#usage-tracking-def';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_main_settings';

	/**
	 * Language to translate.
	 *
	 * @var string
	 * @defender_property
	 */
	public $translate;

	/**
	 * Enable or disable 'Usage Tracking' option.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $usage_tracking = false;

	/**
	 * Enable or disable 'Uninstall Data' option.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[keep,remove]
	 */
	public $uninstall_data = 'keep';

	/**
	 * Enable or disable 'Uninstall Settings' option.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[preserve,reset]
	 */
	public $uninstall_settings = 'preserve';

	/**
	 * Enable or disable 'High Contrast Mode' option.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $high_contrast_mode = false;

	/**
	 * Enable or disable 'Uninstall Quarantine' option.
	 *
	 * @var string
	 * @sanitize_text_field
	 * @defender_property
	 */
	public $uninstall_quarantine = 'keep';

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'usage_tracking', 'high_contrast_mode' ), 'boolean' ),
		array( array( 'uninstall_data' ), 'in', array( 'keep', 'remove' ) ),
		array( array( 'uninstall_settings' ), 'in', array( 'preserve', 'reset' ) ),
	);

	/**
	 * After loading, this function retrieves the site's locale and determines the site's language.
	 *
	 * @return void
	 */
	protected function after_load(): void {
		$site_locale = is_multisite() ? get_site_option( 'WPLANG' ) : get_locale();

		if ( empty( $site_locale ) || 'en_US' === $site_locale ) { // @see wp_dropdown_languages() by default empty string for English.
			$site_language = 'English';
		} else {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$translations  = wp_get_available_translations();
			$site_language = isset( $translations[ $site_locale ] )
				? $translations[ $site_locale ]['native_name']
				: esc_html__( 'Error detecting language', 'wpdef' );
		}

		$this->translate = $site_language;
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'translate'          => esc_html__( 'Translations', 'wpdef' ),
			'usage_tracking'     => esc_html__( 'Usage Tracking', 'wpdef' ),
			'uninstall_data'     => esc_html__( 'Uninstall Data', 'wpdef' ),
			'uninstall_settings' => esc_html__( 'Uninstall Settings', 'wpdef' ),
			'high_contrast_mode' => esc_html__( 'High Contrast Mode', 'wpdef' ),
		);
	}

	/**
	 * Enable or disable 'Usage Tracking' option.
	 *
	 * @param  bool $state  Enable or disable.
	 *
	 * @return void
	 */
	public function toggle_tracking( bool $state ): void {
		$this->usage_tracking = $state;
		$this->save();
	}
}