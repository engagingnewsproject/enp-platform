<?php
/**
 * Loads all CSS and JS files that VFB needs
 *
 * This class should be called when the menu is added
 * so the CSS and JS is added to ONLY our VFB pages.
 *
 */
class Visual_Form_Builder_Scripts_Loader {

	/**
	 * Load CSS on VFB pages.
	 *
	 * @access public
	 * @return void
	 */
	public function add_css() {
		wp_register_style( 'vfb-jqueryui-css', apply_filters( 'vfb-date-picker-css', VFB_WP_PLUGIN_URL . "public/assets/css/smoothness/jquery-ui-1.10.3.min.css" ), array(), '2013.12.03' );
		wp_register_style( 'visual-form-builder-css', apply_filters( 'visual-form-builder-css', VFB_WP_PLUGIN_URL . "public/assets/css/visual-form-builder.min.css" ), array(), '2014.04.12' );

		$vfb_settings = get_option( 'vfb-settings' );

		// Settings - Always load CSS
		if ( isset( $vfb_settings['always-load-css'] ) ) {
			wp_enqueue_style( 'visual-form-builder-css' );
			wp_enqueue_style( 'vfb-jqueryui-css' );

			return;
		}

		// Settings - Disable CSS
		if ( isset( $vfb_settings['disable-css'] ) )
			return;

		// Get active widgets
		$widget = is_active_widget( false, false, 'vfb_widget' );

		// If no widget is found, test for shortcode
		if ( empty( $widget ) ) {
			// If WordPress 3.6, use internal function. Otherwise, my own
			if ( function_exists( 'has_shortcode' ) ) {
				global $post;

				// If no post exists, exit
				if ( !$post )
					return;

				if ( !has_shortcode( $post->post_content, 'vfb' ) )
					return;
			} elseif ( !$this->has_shortcode( 'vfb' ) ) {
				return;
			}
		}

		wp_enqueue_style( 'visual-form-builder-css' );
		wp_enqueue_style( 'vfb-jqueryui-css' );
	}

	/**
	 * Load JS on VFB pages
	 *
	 * @access public
	 * @return void
	 */
	public function add_js() {
		wp_register_script( 'jquery-form-validation', VFB_WP_PLUGIN_URL . "public/assets/js/jquery.validate.min.js", array( 'jquery' ), '1.9.0', true );
		wp_register_script( 'visual-form-builder-validation', VFB_WP_PLUGIN_URL . "public/assets/js/vfb-validation.min.js", array( 'jquery', 'jquery-form-validation' ), '2014.04.12', true );
		wp_register_script( 'visual-form-builder-metadata', VFB_WP_PLUGIN_URL . "public/assets/js/jquery.metadata.js", array( 'jquery', 'jquery-form-validation' ), '2.0', true );
		wp_register_script( 'vfb-ckeditor', VFB_WP_PLUGIN_URL . "public/assets/js/ckeditor/ckeditor.js", array( 'jquery' ), '4.1', true );

		$locale       = get_locale();
        $translations = array(
        	'cs_CS',	// Czech
        	'de_DE',	// German
        	'el_GR',	// Greek
        	'en_US',	// English (US)
        	'en_AU',	// English (AU)
        	'en_GB',	// English (GB)
        	'es_ES',	// Spanish
        	'fr_FR',	// French
        	'he_IL', 	// Hebrew
        	'hu_HU',	// Hungarian
        	'id_ID',	// Indonseian
        	'it_IT',	// Italian
        	'ja_JP',	// Japanese
        	'ko_KR',	// Korean
        	'nl_NL',	// Dutch
        	'pl_PL',	// Polish
        	'pt_BR',	// Portuguese (Brazilian)
        	'pt_PT',	// Portuguese (European)
        	'ro_RO',	// Romanian
        	'ru_RU',	// Russian
        	'sv_SE',	// Swedish
        	'tr_TR', 	// Turkish
        	'zh_CN',	// Chinese
        	'zh_TW',	// Chinese (Taiwan)
        );

		// Load localized vaidation and datepicker text, if translation files exist
        if ( in_array( $locale, $translations ) ) {
            wp_register_script( 'vfb-validation-i18n', VFB_WP_PLUGIN_URL . "public/assets/js/i18n/validate/messages-$locale.js", array( 'jquery-form-validation' ), '1.9.0', true );
            wp_register_script( 'vfb-datepicker-i18n', VFB_WP_PLUGIN_URL . "public/assets/js/i18n/datepicker/datepicker-$locale.js", array( 'jquery-ui-datepicker' ), '1.0', true );
        }
        // Otherwise, load English translations
        else {
	        wp_register_script( 'vfb-validation-i18n', VFB_WP_PLUGIN_URL . "public/assets/js/i18n/validate/messages-en_US.js", array( 'jquery-form-validation' ), '1.9.0', true );
            wp_register_script( 'vfb-datepicker-i18n', VFB_WP_PLUGIN_URL . "public/assets/js/i18n/datepicker/datepicker-en_US.js", array( 'jquery-ui-datepicker' ), '1.0', true );
        }
	}

	/**
	 * Check whether the content contains the specified shortcode
	 *
	 * @access public
	 * @param string $shortcode (default: '')
	 * @return void
	 */
	public function has_shortcode( $shortcode = '' ) {

		$post_to_check = get_post( get_the_ID() );

		// false because we have to search through the post content first
		$found = false;

		// if no short code was provided, return false
		if ( !$shortcode ) {
			return $found;
		}

		// check the post content for the short code
		if ( stripos( $post_to_check->post_content, '[' . $shortcode ) !== false ) {
			// we have found the short code
			$found = true;
		}

		// return our final results
		return $found;
	}
}
