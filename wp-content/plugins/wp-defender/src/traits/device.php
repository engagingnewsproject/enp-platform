<?php
/**
 * Device helper.
 *
 * @package WP_Defender\Traits
 * @since      4.2.0
 */

namespace WP_Defender\Traits;

trait Device {

	/**
	 * Detect if the device is a tablet.
	 *
	 * @return bool
	 */
	private function is_tablet() {
		$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
		if ( empty( $user_agent ) ) {
			return false;
		}
		/**
		 * It doesn't work with IpadOS due to of this:
		 * https://stackoverflow.com/questions/62323230/how-can-i-detect-with-php-that-the-user-uses-an-ipad-when-my-user-agent-doesnt-c
		 */
		$tablet_pattern = '/(tablet|ipad|playbook|kindle|silk)/i';

		return preg_match( $tablet_pattern, $user_agent );
	}

	/**
	 * Detect if the device is a mobile.
	 *
	 * @return bool|string
	 */
	private function is_mobile() {
		$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
		if ( empty( $user_agent ) ) {
			return false;
		}
		// Do not use wp_is_mobile() since it doesn't detect ipad/tablet.
		$mobile_patten = '/Mobile|iP(hone|od|ad)|Android|BlackBerry|tablet|IEMobile|Kindle|NetFront|Silk|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune|playbook/i';

		return preg_match( $mobile_patten, $user_agent );
	}

	/**
	 * Get current device type.
	 *
	 * @return string
	 */
	protected function get_device() {
		if ( $this->is_mobile() ) {
			return 'mobile';
		}

		if ( $this->is_tablet() ) {
			return 'tablet';
		}

		return 'desktop';
	}
}