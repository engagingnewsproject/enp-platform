<?php
/**
 * Handles Hub Connector feature.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Calotes\Component\Response;
use WP_Defender\Controller;
use WP_Defender\Component\Hub_Connector as Hub_Connector_Component;
use WP_Defender\Traits\Defender_Dashboard_Client;

/**
 * Handles Hub Connector feature.
 */
class Hub_Connector extends Controller {
	use Defender_Dashboard_Client;

	public const TRANSIENT_KEY = 'wpdef_maybe_hub_connection_attempt';

	/**
	 * Service for handling logic.
	 *
	 * @var Hub_Connector_Component
	 */
	public $service;

	/**
	 * Constructor for the Hub_Connector class.
	 *
	 * @param Hub_Connector_Component $service The service object.
	 */
	public function __construct( Hub_Connector_Component $service ) {
		$this->service = $service;
		$this->register_routes();

		add_action( 'admin_init', array( $this, 'maybe_hcm_connection_attempt' ) );
	}

	/**
	 * Maybe member is trying to connect via Hub Connection Module.
	 */
	public function maybe_hcm_connection_attempt() {
		$module_slug = defender_get_data_from_request( 'module_slug', 'g' );
		$is_callback = defender_get_data_from_request( 'hub_connector_callback', 'g' );

		if ( ! empty( $module_slug ) && ! empty( $is_callback ) ) {
			set_site_transient(
				self::TRANSIENT_KEY,
				array(
					'module_slug' => $module_slug,
				),
				MINUTE_IN_SECONDS
			);
		}
	}

	/**
	 * Activate Dashboard plugin.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function activate_dashboard_plugin(): Response {
		$plugin_path = 'wpmudev-updates/update-notifications.php';
		$result      = activate_plugin( $plugin_path );
		if ( is_wp_error( $result ) ) {
			return new Response(
				false,
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		return new Response(
			true,
			array(
				'message'  => esc_html__( 'Dashboard plugin has been activated. You should be redirected to sign-in page shortly.', 'wpdef' ),
				'redirect' => $this->service->get_url(),
			)
		);
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'button_label'      => $this->service->get_button_label(),
				'is_dash_installed' => $this->is_dash_installed(),
				'is_dash_activated' => $this->is_dash_activated(),
				'is_hub_connected'  => $this->is_site_connected_to_hub_via_hcm_or_dash(),
				'hub_connector_url' => array(
					'default'     => $this->service->get_url(),
					'global-ip'   => $this->service->get_url( 'wdf-ip-lockout', 'global-ip' ),
					'blocklist'   => $this->service->get_url( 'wdf-ip-lockout', 'blocklist' ),
					'onboard'     => $this->service->get_url( 'wp-defender', 'onboard' ),
					'dashboard'   => $this->service->get_url( 'wp-defender', 'dashboard' ),
					// Custom one if a trigger is the Summary section.
					'summary-box' => $this->service->get_url( 'wdf-ip-lockout', 'summary-box' ),
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Export to array
	 */
	public function to_array() {}

	/**
	 * Import data
	 *
	 * @param array $data The data to import.
	 */
	public function import_data( $data ) {}

	/**
	 * Export strings
	 *
	 * @return array
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Removes settings.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}
}