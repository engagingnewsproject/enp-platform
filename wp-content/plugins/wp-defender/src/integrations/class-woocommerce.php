<?php

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Woocommerce integration module.
 * Class Woocommerce
 *
 * @since 2.6.1
 * @package WP_Defender\Integrations
 */
class Woocommerce {

	/**
	 * Check if Woo is activated.
	 *
	 * @return bool
	 */
	public function is_activated() {

		return class_exists( 'woocommerce' );
	}
}