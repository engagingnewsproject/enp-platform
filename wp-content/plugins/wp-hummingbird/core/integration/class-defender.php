<?php
/**
 * Defender integration.
 *
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Defender
 */
class Defender {

	/**
	 * WPDefender constructor.
	 */
	public function __construct() {
		if ( ! $this->is_active() ) {
			return;
		}

		// Redirect Current Hummingbird admin URL to Mask Login URL.
		add_filter( 'wpdef_maybe_redirect_to_mask_login_url', array( $this, 'maybe_redirect_to_mask_login_url' ), 10, 2 );
	}

	/**
	 * Check and redirect current Hummingbird admin URL to mask login URL.
	 *
	 * @param bool   $allowed     Should we redirect to Mask Login URL?.
	 * @param string $current_url Current URL to check.
	 * @return bool
	 */
	public function maybe_redirect_to_mask_login_url( $allowed, $current_url ) {
		$current_page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
		$current_page = sanitize_text_field( $current_page );

		if ( is_admin() && ! empty( $current_page ) && false !== strpos( $current_page, 'wphb' ) ) {
			return true;
		}

		return $allowed;
	}

	/**
	 * Check if Defender plugin is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return apply_filters( 'wphb_defender_is_active', defined( 'DEFENDER_VERSION' ) && DEFENDER_VERSION );
	}

}