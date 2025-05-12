<?php
/**
 * Handles interactions with Woocommerce.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Woocommerce integration module.
 *
 * @since 2.6.1
 * @since 3.3.0 Add locations.
 */
class Woocommerce {

	public const WOO_LOGIN_FORM = 'woo_login',
		WOO_REGISTER_FORM       = 'woo_register',
		WOO_LOST_PASSWORD_FORM  = 'woo_lost_password',
		WOO_CHECKOUT_FORM       = 'woo_checkout';

	/**
	 * Check if Woo is activated.
	 *
	 * @return bool
	 */
	public function is_activated(): bool {
		return class_exists( 'woocommerce' );
	}

	/**
	 * Retrieves an array of WooCommerce forms with their respective translations.
	 *
	 * @return array An associative array where the keys are the form identifiers and the values are the translated
	 *     form names.
	 */
	public static function get_forms(): array {
		return array(
			self::WOO_LOGIN_FORM         => esc_html__( 'Login', 'wpdef' ),
			self::WOO_REGISTER_FORM      => esc_html__( 'Registration', 'wpdef' ),
			self::WOO_LOST_PASSWORD_FORM => esc_html__( 'Lost Password', 'wpdef' ),
			self::WOO_CHECKOUT_FORM      => esc_html__( 'Checkout', 'wpdef' ),
		);
	}
}