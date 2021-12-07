<?php
/**
 * Cloudflare service.
 *
 * @package Hummingbird\Core\Api\Service\
 */

namespace Hummingbird\Core\Api\Service;

use Hummingbird\Core\Api\Exception;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cloudflare
 */
class Cloudflare extends Service {

	/**
	 * Service name.
	 *
	 * @var string
	 */
	protected $name = 'cloudflare';

	/**
	 * Cloudflare constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		$this->request = new \Hummingbird\Core\Api\Request\Cloudflare( $this );
		$this->refresh_auth();
	}

	/**
	 * Refresh auth.
	 *
	 * Sometimes, especially during AJAX requests, when the module was already initialized and the credentials were
	 * updated, we need to refresh the auth.
	 *
	 * @since 3.1.0
	 */
	public function refresh_auth() {
		$settings = \Hummingbird\Core\Settings::get_settings( 'cloudflare' );

		if ( ! isset( $settings['api_key'] ) || empty( $settings['api_key'] ) ) {
			return;
		}

		if ( isset( $settings['email'] ) && ! empty( $settings['email'] ) ) {
			$this->request->set_auth_email( $settings['email'] );
			$this->request->set_auth_key( $settings['api_key'] );
		} else {
			$this->request->set_auth_token( $settings['api_key'] );
		}
	}

	/**
	 * Set zone.
	 *
	 * @param string $zone  Zone.
	 */
	public function set_zone( $zone ) {
		$this->request->set_zone( $zone );
	}

	/**
	 * Get zone list.
	 *
	 * @param int $page      Current page.
	 * @param int $per_page  Zones per page.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_zones_list( $page = 1, $per_page = 20 ) {
		return $this->request->get(
			'zones',
			array(
				'per_page' => $per_page,
				'page'     => $page,
			)
		);
	}

	/**
	 * Get page rules list.
	 *
	 * @param string $zone  Zone.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_page_rules_list( $zone ) {
		return $this->request->get( "zones/{$zone}/pagerules" );
	}

	/**
	 * Add page rule.
	 *
	 * @param array    $targets   Targets to evaluate on a request.
	 * @param array    $actions   The set of actions to perform if the targets of this rule match the request.
	 * @param string   $zone      Zone.
	 * @param string   $status    Status of the page rule. Valid values: active, disabled.
	 * @param int|null $priority  A number that indicates the preference for a page rule over another.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function add_page_rule( $targets, $actions, $zone, $status = 'active', $priority = null ) {
		$data = array(
			'targets'  => $targets,
			'actions'  => $actions,
			'priority' => $priority,
			'status'   => $status,
		);

		return $this->request->post( "zones/{$zone}/pagerules", wp_json_encode( $data ) );
	}

	/**
	 * Update page rules.
	 *
	 * @param string   $id        Page rule ID.
	 * @param array    $targets   Targets to evaluate on a request.
	 * @param array    $actions   The set of actions to perform if the targets of this rule match the request.
	 * @param string   $zone      Zone.
	 * @param string   $status    Status. Valid values: active, disabled.
	 * @param int|null $priority  A number that indicates the preference for a page rule over another.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function update_page_rule( $id, $targets, $actions, $zone, $status = 'active', $priority = null ) {
		$data = array(
			'targets'  => $targets,
			'actions'  => $actions,
			'priority' => $priority,
			'status'   => $status,
		);

		return $this->request->patch( "zones/{$zone}/pagerules/{$id}", wp_json_encode( $data ) );
	}

	/**
	 * Remove page rule.
	 *
	 * @param string $id    Page rule ID.
	 * @param string $zone  Zone.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function delete_page_rule( $id, $zone ) {
		return $this->request->delete( "zones/{$zone}/pagerules/{$id}" );
	}

	/**
	 * Set caching expiration.
	 *
	 * @param string $zone  Zone.
	 * @param int    $value Value of the zone setting. Default value: 14400.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function set_caching_expiration( $zone, $value ) {
		$data = array(
			'value' => $value,
		);
		return $this->request->patch( "zones/{$zone}/settings/browser_cache_ttl", wp_json_encode( $data ) );
	}

	/**
	 * Get expiration data.
	 *
	 * @param string $zone  Zone.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_caching_expiration( $zone ) {
		return $this->request->get( "zones/{$zone}/settings/browser_cache_ttl" );
	}

	/**
	 * Purge cache.
	 *
	 * @param string $zone  Zone ID.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function purge_cache( $zone ) {
		return $this->request->delete(
			"zones/{$zone}/purge_cache",
			wp_json_encode(
				array(
					'purge_everything' => true,
				)
			)
		);
	}

	/**
	 * Clear out a list of URLs.
	 *
	 * @since 3.0.0
	 *
	 * @see https://api.cloudflare.com/#zone-purge-files-by-url
	 *
	 * @param array $urls  An array of URLs that should be removed from cache. Limit 30 per request.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function purge_urls( $urls ) {
		return $this->request->delete(
			'zones/%ZONE%/purge_cache',
			wp_json_encode(
				array(
					'files' => $urls,
				)
			)
		);
	}

	/**
	 * Get entitlements. Allows getting services that are available on the users account.
	 *
	 * @since 3.0.0
	 */
	public function get_entitlements() {
		return $this->request->get( 'zones/%ZONE%/entitlements' );
	}

	/**
	 * Get Automatic Platform Optimization for WordPress setting.
	 *
	 * @since 3.0.0
	 *
	 * @see https://api.cloudflare.com/#zone-settings-get-automatic-platform-optimization-for-wordpress-setting
	 * @see set_apo_settings for a list of return values.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_apo_settings() {
		return $this->request->get( 'zones/%ZONE%/settings/automatic_platform_optimization' );
	}

	/**
	 * Change Automatic Platform Optimization for WordPress setting.
	 *
	 * @since 3.0.0
	 *
	 * @see https://api.cloudflare.com/#zone-settings-change-automatic-platform-optimization-for-wordpress-setting
	 * @see https://support.cloudflare.com/hc/en-us/articles/360049822312#h_01ERZ6QHBGFVPSC44SJAC1YM6Q
	 *
	 * @param array $value {
	 *     An array of properties.
	 *
	 *     @type bool  $enabled               Indicates whether or not APO is enabled.
	 *                                        Default 'false'. Accepts 'true', 'false'.
	 *     @type bool  $cf                    Indicates whether or not Cloudflare proxy is enabled.
	 *                                        Default 'false'. Accepts 'true', 'false'.
	 *     @type bool  $wordpress             Indicates whether or not site is powered by WordPress.
	 *                                        Default 'false'. Accepts 'true', 'false'.
	 *     @type bool  $wp_plugin             Indicates whether or not Hummingbird plugin is installed.
	 *                                        Default 'false'. Accepts 'true', 'false'.
	 *     @type array $hostnames             An array of hostnames where Automatic Platform Optimization for WordPress is activated.
	 *                                        Default 'null'.
	 *     @type bool  $cache_by_device_type  Indicates whether or not Cache by Device Type is enabled.
	 *                                        Default 'null'. Accepts 'true', 'false'.
	 * }
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function set_apo_settings( $value ) {
		$data = array(
			'value' => $value,
		);

		return $this->request->patch(
			'zones/%ZONE%/settings/automatic_platform_optimization',
			wp_json_encode( $data )
		);
	}

}
