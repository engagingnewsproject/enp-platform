<?php
/**
 * It handles functionalities such as saving settings, fetching and syncing global IP lists from the HUB.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Controller;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Setting;
use WP_Defender\Traits\Formats;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\IP\Global_IP as Global_IP_Component;

/**
 * Handles global IP lockout settings.
 */
class Global_Ip extends Controller {

	use Setting;
	use Formats;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * The model for handling the data.
	 *
	 * @var Global_Ip_Lockout
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Global_IP_Component
	 */
	protected $service;

	/**
	 * The WPMUDEV instance used for interacting with WPMUDEV services.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Global_Ip_Lockout::class );
		$this->service = wd_di()->get( Global_IP_Component::class );
		$this->wpmudev = wd_di()->get( WPMUDEV::class );

		/**
		 * Network Cron Manager
		 *
		 * @var Network_Cron_Manager $network_cron_manager
		 */
		$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
		$network_cron_manager->register_callback(
			'wpdef_fetch_global_ip_list',
			array( $this, 'fetch_global_ip_list' ),
			HOUR_IN_SECONDS
		);

		if ( $this->service->can_blocklist_autosync() ) {
			// No need to run Rate mechanism for IP lockouts because we do it in Blacklist class.
			add_action( 'wd_blacklist_this_ip', array( $this, 'blacklist_an_ip' ) );
		}
		add_action( 'init', array( $this->service, 'handle_expired_membership' ) );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data(
			array(
				'enabled'            => array( 'type' => 'bool' ),
				'allow_self_unlock'  => array( 'type' => 'bool' ),
				'blocklist_autosync' => array( 'type' => 'bool' ),
				// Temporary property.
				'module_title'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				// End.
			)
		);

		$message_type = 'central_ip';
		// Split messages.
		if ( isset( $data['module_title'] ) && 'antibot' === $data['module_title'] ) {
			$message_type = 'antibot';
			unset( $data['module_title'] );
		}

		$old_enabled     = (bool) $this->model->enabled;
		$old_self_unlock = $this->model->allow_self_unlock;

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();
			if ( 'central_ip' === $message_type ) {
				$message = $this->get_update_message( $data, $old_enabled, Global_Ip_Lockout::get_module_name() );
			} else {
				$message = '';
				if ( ! empty( $data['allow_self_unlock'] ) ) {
					$message = esc_html__( 'Temporary self unlock CAPTCHA challenge is enabled successfully.', 'wpdef' );
				} elseif ( ! empty( $old_self_unlock ) && empty( $data['allow_self_unlock'] ) ) {
					$message = esc_html__( 'Temporary self unlock CAPTCHA challenge is disabled successfully.', 'wpdef' );
				}
			}

			return new Response(
				true,
				array_merge(
					array(
						'message'    => $message,
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array( 'message' => $this->model->get_formatted_errors() )
		);
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( $this->is_page_active() ) {
			wp_localize_script( 'def-iplockout', 'global_ip', $this->data_frontend() );
		}
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'model' => $this->model->export(),
				'misc'  => array(
					'show_global_ips_disable'  => $this->wpmudev->is_disabled_hub_option(),
					'is_wpmu_dev_admin'        => $this->wpmudev->is_wpmu_dev_admin(),
					'module_name'              => Global_Ip_Lockout::get_module_name(),
					'text_to_connect'          => sprintf(
						/* translators: %s: Module name. */
						esc_html__( 'Connect to a WPMU DEV account to activate %s.', 'wpdef' ),
						Global_Ip_Lockout::get_module_name()
					),
					'is_show_dashboard_notice' => $this->service->is_show_dashboard_notice(),
					'current_plan'             => $this->service->get_membership_type(),
					'is_expired_membership'    => $this->service->is_expired_membership_type(),
				),
				'hub'   => array(
					'global_ip_list'        => $this->service->get_formated_global_ip_list(),
					'global_ip_setting_url' => $this->wpmudev->get_api_base_url() . 'hub2/ip-banning',
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Fetch Global IP list from HUB.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function fetch_global_ip_list(): void {
		if ( true === $this->model->enabled ) {
			$this->service->fetch_global_ip_list();
		}
	}

	/**
	 * Refresh Global IP list.
	 *
	 * @return Response
	 * @defender_route
	 * @since 3.4.0
	 */
	public function refresh_global_ip_list(): Response {
		$data = $this->service->fetch_global_ip_list();

		if ( ! is_wp_error( $data ) ) {
			return new Response(
				true,
				array(
					'message'        => esc_html__(
						'The Custom IP List has been updated successfully.',
						'wpdef'
					),
					'global_ip_list' => $this->service->get_formated_global_ip_list(),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => esc_html__(
						'An error occurred while synchronizing the Custom IP List.',
						'wpdef'
					),
				)
			);
		}
	}

	/**
	 * Add an IP to blacklist.
	 *
	 * @param  string $ip  The IP to blacklist.
	 *
	 * @return void
	 */
	public function blacklist_an_ip( string $ip ): void {
		$data = array(
			'block_list' => array( $ip ),
		);
		$this->service->add_to_global_ip_list( $data );
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$model = $this->model;
		if ( isset( $data['global_ip_list'] ) ) {
			$model->enabled = (bool) $data['global_ip_list'];
			if ( isset( $data['global_ip_list_blocklist_autosync'] ) ) {
				$model->blocklist_autosync = (bool) $data['global_ip_list_blocklist_autosync'];
			}
		} else {
			$model->enabled            = false;
			$model->blocklist_autosync = false;
		}
		$model->save();
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
		delete_site_transient( Global_IP_Component::LIST_KEY );
		delete_site_option( Global_IP_Component::LIST_LAST_SYNCED_KEY );
		$this->service->delete_dashboard_notice_reminder();
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings() {
		return array();
	}
}