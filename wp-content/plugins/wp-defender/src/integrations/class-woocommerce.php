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
	 * Detects if the request is coming from a WooCommerce login context.
	 *
	 * @return bool
	 */
	public function is_wc_login_context(): bool {
		if ( ! $this->is_activated() ) {
			return false;
		}

		// Problem: REST_REQUEST constant is false during Store API requests, so we check the rest_route parameter instead.
		$request_uri = defender_get_data_from_request( 'REQUEST_URI', 's' );
		if ( '' !== $request_uri && strpos( $request_uri, 'rest_route=/wc/store/v1/checkout' ) !== false ) {
			return true;
		}

		$post_data = defender_get_data_from_request( null, 'p' );

		if ( 0 === count( $post_data ) ) {
			return false;
		}

		if (
			isset( $post_data['woocommerce-login-nonce'] ) ||
			isset( $post_data['woocommerce-register-nonce'] ) ||
			isset( $post_data['woocommerce_checkout_login'] ) ||
			( isset( $post_data['login'] ) && is_checkout() ) ||
			( isset( $post_data['register'] ) && isset( $post_data['email'] ) ) ||
			isset( $post_data['wc_reset_password'] )
		) {
			return true;
		}

		$referer = wp_get_referer();
		if ( $referer && function_exists( 'wc_get_page_id' ) ) {
			$my_account_page_id = wc_get_page_id( 'myaccount' );
			if ( $my_account_page_id > 0 ) {
				$my_account_url = get_permalink( $my_account_page_id );

				return strpos( $referer, $my_account_url ) === 0;
			}
		}

		return false;
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