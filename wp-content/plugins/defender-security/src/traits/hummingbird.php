<?php
/**
 * Hummingbird trait.
 *
 * @since 2.6.1
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Hummingbird
 */
trait Hummingbird {
	/**
	 * Check if Hummingbird is active.
	 *
	 * @return bool
	 */
	public function is_hummingbird_enabled() {
		return class_exists( 'Hummingbird\\WP_Hummingbird' );
	}

	/**
	 * Check if Hummingbird has lazy load comments enabled.
	 *
	 * @return bool
	 */
	public function is_lazy_load_comments_enabled() {
		if ( ! $this->is_hummingbird_enabled() ) {
			return false;
		}

		$settings = is_multisite() ? get_site_option( 'wphb_settings', array() ) : get_option( 'wphb_settings', array() );
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return false;
		}

		return ! empty( $settings['advanced']['lazy_load']['enabled'] ) && $settings['advanced']['lazy_load']['enabled'];
	}
}