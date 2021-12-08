<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Main_Setting extends Setting {
	/**
	 * Option name
	 * @var string
	 */
	public $table = 'wd_main_settings';

	/**
	 * @var string
	 * @defender_property
	 */
	public $translate;
	/**
	 * @var bool
	 * @defender_property
	 */
	public $usage_tracking = false;
	/**
	 * @var string
	 * @sanitize_text_field
	 * @defender_property
	 */
	public $uninstall_data = 'keep';

	/**
	 * @var string
	 * @sanitize_text_field
	 * @defender_property
	 */
	public $uninstall_settings = 'preserve';

	/**
	 * @var bool
	 * @defender_property
	 */
	public $high_contrast_mode = false;

	protected function after_load() {
		$site_locale = is_multisite() ? get_site_option( 'WPLANG' ) : get_locale();

		if ( empty( $site_locale ) || 'en_US' === $site_locale ) { // @see wp_dropdown_languages() by default empty string for English.
			$site_language = 'English';
		} else {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$translations  = wp_get_available_translations();
			$site_language = isset( $translations[ $site_locale ] )
				? $translations[ $site_locale ]['native_name']
				: __( 'Error detecting language', 'wpdef' );
		}

		$this->translate = $site_language;
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
			'translate'          => __( 'Translations', 'wpdef' ),
			'usage_tracking'     => __( 'Usage Tracking', 'wpdef' ),
			'uninstall_data'     => __( 'Uninstall Data', 'wpdef' ),
			'uninstall_settings' => __( 'Uninstall Settings', 'wpdef' ),
			'high_contrast_mode' => __( 'High Contrast Mode', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}