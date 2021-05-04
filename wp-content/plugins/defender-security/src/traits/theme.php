<?php

namespace WP_Defender\Traits;

trait Theme {

	/**
	 * Get all installed themes.
	 *
	 * @return array
	 */
	public function get_themes() {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once( ABSPATH . '/wp-includes/theme.php' );
		}
		return wp_get_themes();
	}
}
