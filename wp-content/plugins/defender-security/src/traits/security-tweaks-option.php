<?php
/**
 * Handle persistent storage of security tweak option.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

/**
 * Traits to handle persistent storage of security tweak option.
 *
 * @package WP_Defender\Traits
 */
trait Security_Tweaks_Option {
	/**
	 * Generic method to update security tweak site option.
	 *
	 * @param string $key   Name of the security tweak option.
	 * @param mixed  $value Value of the security tweak option.
	 *
	 * @return bool True if the value was updated, false otherwise.
	 */
	public function update_option( $key, $value ) {
		$option_name = self::OPTION_PREFIX . $this->slug;

		$options = get_site_option( $option_name );

		$options[ $key ] = $value;

		return update_site_option( $option_name, $options );
	}

	/**
	 * Generic method to get security tweak site option value.
	 *
	 * @param string $key Name of the security tweak option.
	 *
	 * @return mixed Value of the security tweak option.
	 */
	public function get_option( $key ) {
		$option_name = self::OPTION_PREFIX . $this->slug;

		$options = get_site_option( $option_name, array() );

		return array_key_exists( $key, $options ) ? $options[ $key ] : null;
	}
}
