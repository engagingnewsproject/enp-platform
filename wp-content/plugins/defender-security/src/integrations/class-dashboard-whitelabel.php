<?php
declare( strict_types=1 );

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class Dashboard_Whitelabel
 *
 * @since 3.2.0
 * @package WP_Defender\Integrations
 */
class Dashboard_Whitelabel {

	/**
	 * @var array Holds dashboard plugin white-label filter values.
	 */
	private $wpmudev_branding;

	public function __construct() {
		$this->wpmudev_branding = apply_filters( 'wpmudev_branding', [] );
	}

	/**
	 * Hide or show branding.
	 *
	 * @return bool True to hide and false for show.
	 */
	public function is_hide_branding(): bool {
		return isset( $this->wpmudev_branding['hide_branding'] ) && $this->wpmudev_branding['hide_branding'];
	}

	/**
	 * Get branding logo.
	 *
	 * @return string URL of whitelabeled logo or default logo.
	 */
	public function get_branding_logo(): string {
		if ( $this->is_hide_branding() === true && ! empty( trim( $this->wpmudev_branding['hero_image'] ) ) ) {
			return $this->wpmudev_branding['hero_image'];
		}

		return defender_asset_url( '/assets/email-images/logo.png' );
	}

	/**
	 * Boolean to check before change footer text.
	 *
	 * @return bool True to change and false for use default.
	 */
	public function is_change_footer(): bool {
		return isset( $this->wpmudev_branding['change_footer'] ) && $this->wpmudev_branding['change_footer'];
	}

	/**
	 * Footer text either custom text or default text.
	 *
	 * @return string Text to show in email content footer.
	 */
	public function get_footer_text(): string {
		if ( $this->is_change_footer() === true && ! empty( trim( $this->wpmudev_branding['footer_text'] ) ) ) {
			return $this->wpmudev_branding['footer_text'];
		}

		return esc_html__( 'The WPMU DEV Team.', 'wpdef' );
	}

}
