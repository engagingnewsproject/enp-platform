<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that its ready for translation.
 *
 * @since      1.0
 */
class Visual_Form_Builder_i18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $domain    The domain identifier for this plugin.
	 */
	private $domain;

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0
	 */
	public function load_lang() {

		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

		$wp_lang_dir = WP_LANG_DIR . '/' . $this->domain . '/' . $locale . '.mo';

		// Load translated strings from WP_LANG_DIR
		load_textdomain( $this->domain, $wp_lang_dir );

		// Main plugin path
		$plugin_dir  = VFB_WP_PLUGIN_FILE;

		// Lang folder path
		$lang_dir    = dirname( plugin_basename( $plugin_dir ) ) . '/lang/';

		// Load translated strings, if no WP_LANG_DIR found
		load_plugin_textdomain( $this->domain, false, $lang_dir );

	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @since    1.0
	 * @param    string    $domain    The domain that represents the locale of this plugin.
	 */
	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

}
