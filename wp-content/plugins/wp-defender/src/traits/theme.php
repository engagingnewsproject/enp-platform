<?php
/**
 * Helper functions for theme support.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_Theme;

trait Theme {

	/**
	 * No use get_theme_root().
	 *
	 * @return string
	 */
	public function get_path_of_themes_dir(): string {
		return WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get all installed themes.
	 *
	 * @return array
	 */
	public function get_themes(): array {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . '/wp-includes/theme.php';
		}

		return wp_get_themes();
	}

	/**
	 * Get theme's details.
	 *
	 * @return WP_Theme
	 */
	public function get_theme(): WP_Theme {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . '/wp-includes/theme.php';
		}

		return wp_get_theme();
	}

	/**
	 * Get all slugs
	 *
	 * @return array
	 */
	public function get_theme_slugs(): array {
		$slugs = array();
		foreach ( $this->get_themes() as $slug => $theme ) {
			if ( is_object( $theme->parent() ) ) {
				continue;
			}
			$slugs[] = $slug;
		}

		return $slugs;
	}

	/**
	 * Check if the given theme file path is the active theme.
	 *
	 * @param  string $file_path  The absolute file path to the theme file.
	 *
	 * @return bool Returns true if the given theme file path is the active theme, false otherwise.
	 */
	public function is_active_theme( $file_path ): bool {
		$active_theme = $this->get_theme();
		if ( ! is_object( $active_theme ) ) {
			return false;
		}
		$theme_dir = $this->get_path_of_themes_dir();
		$abs_path  = $theme_dir;
		$abs_path  = defender_replace_line( $abs_path );
		// Without the first slash.
		$rev_file   = str_replace( $abs_path, '', $file_path );
		$theme_data = explode( '/', $rev_file );
		if ( ! empty( $theme_data ) ) {
			$theme_slug = $theme_data[0];
		} else {
			return false;
		}
		// Checking if the given theme is a child theme.
		if ( false !== stripos( $theme_slug, '-child' ) ) {
			$theme_slug = str_replace( '-child', '', $theme_slug );
		}
		// Checking if the active theme is a parent theme.
		if ( is_object( $active_theme->parent() ) ) {
			$active_theme_slug = $active_theme->parent()->get_stylesheet();
		} else {
			$active_theme_slug = $active_theme->get_stylesheet();
		}

		return $theme_slug === $active_theme_slug;
	}
}