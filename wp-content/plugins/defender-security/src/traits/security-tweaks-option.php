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

		return $this->update_all_option( $options );
	}

	/**
	 * Generic method to update all option value of specific security tweak.
	 *
	 * @param mixed $value Value of the security tweak option.
	 *
	 * @return bool True if the value was updated, false otherwise.
	 */
	public function update_all_option( $value ) {
		$option_name = self::OPTION_PREFIX . $this->slug;

		return update_site_option( $option_name, $value );
	}

	/**
	 * Generic method to get security tweak site option value.
	 *
	 * @param string $key Name of the security tweak option.
	 *
	 * @return mixed Value of the security tweak option.
	 */
	public function get_option( $key ) {
		$options = $this->get_all_option();

		return array_key_exists( $key, $options ) ? $options[ $key ] : null;
	}

	/**
	 * Generic method to get all option value of specific security tweak.
	 *
	 * @return mixed All value of the specific security tweak.
	 */
	public function get_all_option() {
		$option_name = self::OPTION_PREFIX . $this->slug;

		return get_site_option( $option_name, array() );
	}

	/**
	 * Generic method to delete option of a specific security tweak.
	 *
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function delete_all_option() {
		$option_name = self::OPTION_PREFIX . $this->slug;

		return delete_site_option( $option_name );
	}
}
