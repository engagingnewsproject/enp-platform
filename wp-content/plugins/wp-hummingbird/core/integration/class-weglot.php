<?php
/**
 * Weglot integration class.
 *
 * @since 3.3.3
 *
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Weglot
 */
class Weglot {

	/**
	 * Weglot singleton instance.
	 *
	 * @access private
	 *
	 * @var Weglot $instance|null
	 */
	private static $instance = null;

	/**
	 * Get Weglot singleton instance.
	 *
	 * @return Weglot|null
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Weglot constructor.
	 */
	private function __construct() {
		add_filter( 'wphb_page_cache_request_uri', array( $this, 'wphb_page_cache_request_uri' ) );
	}

	/**
	 * Check if Weglot plugin is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return apply_filters( 'wpph_weglot_is_active', defined( 'WEGLOT_VERSION' ) && WEGLOT_VERSION );
	}

	/**
	 * Add language code in request_uri for the Weglot plugin.
	 *
	 * Fixes translate issue with Weglot.
	 *
	 * @param string $request_uri Request URI.
	 *
	 * @return string
	 */
	public function wphb_page_cache_request_uri( $request_uri ) {
		// Return if Weglot plugin is not activated.
		if ( ! $this->is_active() ) {
			return $request_uri;
		}

		// Make sure the Weglot function we are using exist to avoid fatal errors.
		if ( ! function_exists( 'weglot_get_service' ) ) {
			return $request_uri;
		}

		$request_url_services = weglot_get_service( 'Request_Url_Service_Weglot' );
		$language_services    = weglot_get_service( 'Language_Service_Weglot' );

		// Make sure the Weglot functions we are using exist to avoid fatal errors.
		if ( ! method_exists( $request_url_services, 'get_current_language' ) || ! method_exists( $language_services, 'get_original_language' ) ) {
			return $request_uri;
		}

		$original_language = $language_services->get_original_language();
		$current_language  = $request_url_services->get_current_language( false );

		if ( $original_language && $current_language !== $original_language && method_exists( $current_language, 'getExternalCode' ) ) {
			return '/' . $current_language->getExternalCode() . $request_uri;
		}

		return $request_uri;
	}
}