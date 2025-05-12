<?php
/**
 * Handles interactions with the Dashboard for white label.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

use WPMUDEV_Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Dashboard integration module for whitelabel.
 *
 * @since 3.2.0
 */
class Dashboard_Whitelabel {

	/**
	 * Holds dashboard plugin white-label filter values.
	 *
	 * @var array
	 */
	private $wpmudev_branding;

	/**
	 * Constructor for the class.
	 * Initializes the object by setting the value of the $wpmudev_branding property
	 * by applying the 'wpmudev_branding' filter to an empty array.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->wpmudev_branding = apply_filters( 'wpmudev_branding', array() );
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
	 * @return string URL of whitelabel logo or default logo.
	 */
	public function get_branding_logo(): string {
		if ( $this->is_hide_branding() && ! empty( trim( $this->wpmudev_branding['hero_image'] ) ) ) {
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
		if ( $this->is_change_footer() && $this->is_set_footer_text() ) {
			return $this->wpmudev_branding['footer_text'];
		}

		return esc_html__( 'The WPMU DEV Team.', 'wpdef' );
	}

	/**
	 * Check if whitelabel feature is allowed for the membership.
	 *
	 * @return bool
	 * @since 4.5.0
	 */
	public function can_whitelabel(): bool {
		if (
			class_exists( '\WPMUDEV_Dashboard' ) &&
			is_object( WPMUDEV_Dashboard::$whitelabel ) &&
			method_exists( WPMUDEV_Dashboard::$whitelabel, 'can_whitelabel' ) &&
			WPMUDEV_Dashboard::$whitelabel->can_whitelabel()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if whitelabel footer text is set.
	 *
	 * @return bool
	 * @since 4.5.0
	 */
	public function is_set_footer_text(): bool {
		$text = $this->wpmudev_branding['footer_text'] ?? '';

		return trim( $text ) !== '';
	}

	/**
	 * Whether to custom plugin labels or not.
	 *
	 * @param  int $plugin_id  Plugin id.
	 *
	 * @return bool
	 * @since 4.5.0
	 */
	private function plugin_enabled( $plugin_id ) {
		if (
			! class_exists( '\WPMUDEV_Dashboard' ) ||
			empty( WPMUDEV_Dashboard::$whitelabel ) ||
			! method_exists( WPMUDEV_Dashboard::$whitelabel, 'get_settings' )
		) {
			return false;
		}
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();

		return ! empty( $whitelabel_settings['labels_enabled'] )
				&& ! empty( $whitelabel_settings['labels_config'][ $plugin_id ] );
	}

	/**
	 * Get custom plugin label.
	 *
	 * @param  int $plugin_id  Plugin id.
	 *
	 * @return bool|string
	 * @since 4.5.0
	 */
	public function get_plugin_name( $plugin_id ) {
		if ( ! $this->plugin_enabled( $plugin_id ) ) {
			return false;
		}
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		if ( empty( $whitelabel_settings['labels_config'][ $plugin_id ]['name'] ) ) {
			return false;
		}

		return $whitelabel_settings['labels_config'][ $plugin_id ]['name'];
	}
}