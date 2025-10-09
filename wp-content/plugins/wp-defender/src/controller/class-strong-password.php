<?php
/**
 * Handle strong password module.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Integrations\Woocommerce;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Strong_Password as Service;
use WP_Defender\Model\Setting\Strong_Password as Settings;

/**
 * Handle strong password module.
 */
class Strong_Password extends Event {

	/**
	 * The model for the strong password module.
	 *
	 * @var Settings|null
	 */
	protected ?Settings $model = null;

	/**
	 * The service for the strong password module.
	 *
	 * @var Service|null
	 */
	protected ?Service $service = null;

	/**
	 * Helper instance for reuse methods from pwned password protection.
	 *
	 * @var Password_Protection|null
	 */
	private ?Password_Protection $helper;

	/**
	 * WooCommerce integration instance.
	 *
	 * @var Woocommerce|null
	 */
	private ?Woocommerce $woo;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->model   = wd_di()->get( Settings::class );
		$this->service = wd_di()->get( Service::class );
		$this->helper  = wd_di()->get( Password_Protection::class );
		$this->woo     = wd_di()->get( Woocommerce::class );
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );
		$this->register_routes();
		if ( $this->model->is_active() ) {
			if ( ! wp_using_themes() ) {
				$this->setup_core_hooks();
			}
			if ( $this->woo->is_activated() ) {
				add_action( 'woocommerce_init', array( $this, 'setup_woo_hooks' ) );
			}
		}
	}

	/**
	 * Set up core WordPress hooks for strong password functionality.
	 * These hooks work independently of WooCommerce settings.
	 */
	private function setup_core_hooks() {
		// Update site url on sub-site when MaskLogin is disabled.
		if (
			is_multisite() && ! is_main_site()
			&& ! wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->is_active()
		) {
			add_filter( 'network_site_url', array( $this->helper, 'filter_site_url' ), 100, 2 );
		}

		// Remove conflicting plugins.
		remove_action( 'user_profile_update_errors', 'slt_fsp_validate_profile_update', 0 );
		remove_action( 'resetpass_form', 'slt_fsp_validate_resetpass_form', 10 );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this->service, 'scripts' ) );
			add_action( 'user_profile_update_errors', array( $this->service, 'on_profile_update' ), 100, 3 );
		}

		// Core WordPress authentication and password hooks.
		add_action( 'login_enqueue_scripts', array( $this->service, 'scripts' ) );
		add_action( 'validate_password_reset', array( $this->service, 'on_password_reset' ), 100, 2 );
		add_action( 'wp_authenticate_user', array( $this->service, 'during_core_authentication' ), 100, 2 );
		add_filter( 'random_password', array( $this->service, 'generate_password' ), 10, 2 );
		add_filter( 'lostpassword_url', array( $this->service, 'lostpassword_url' ), 10, 1 );
	}

	/**
	 * Add hooks for handling strong password in WooCommerce forms.
	 */
	public function setup_woo_hooks() {
		$woo_locations = $this->model->forms['woocommerce'] ?? array();

		if ( array() === $woo_locations ) {
			return;
		}

		if ( in_array( Woocommerce::WOO_LOGIN_FORM, $woo_locations, true ) ) {
			add_filter( 'woocommerce_process_login_errors', array( $this->service, 'during_woo_authentication' ), 10, 3 );
		}

		$referer                      = wp_get_referer();
		$add_woo_reset_password_hooks = false;
		if ( $referer && function_exists( 'wc_get_page_id' ) ) {
			$my_account_page_id = wc_get_page_id( 'myaccount' );
			if ( $my_account_page_id > 0 ) {
				$my_account_url               = get_permalink( $my_account_page_id );
				$add_woo_reset_password_hooks = strpos( $referer, $my_account_url ) === 0;
			}
		}

		if (
			in_array( Woocommerce::WOO_LOST_PASSWORD_FORM, $woo_locations, true ) ||
			$add_woo_reset_password_hooks
		) {
			add_action( 'wp_enqueue_scripts', array( $this->service, 'scripts' ) );
			add_action( 'validate_password_reset', array( $this->service, 'on_password_reset' ), 100, 2 );
			add_filter( 'woocommerce_reset_password_message', array( $this->service, 'add_woocommerce_error_message' ) );
			add_action( 'woocommerce_save_account_details_errors', array( $this->service, 'on_woo_account_update' ), 10, 2 );
		}

		if (
			in_array( Woocommerce::WOO_REGISTER_FORM, $woo_locations, true ) ||
			in_array( Woocommerce::WOO_CHECKOUT_FORM, $woo_locations, true )
		) {
			add_action( 'wp_enqueue_scripts', array( $this->service, 'scripts' ) );
			add_filter( 'woocommerce_registration_errors', array( $this->service, 'during_woo_registration' ) );
		}
	}

	/**
	 * Provide data to the frontend via localized script.
	 *
	 * @param  array $data  Data collection is ready to passed.
	 *
	 * @return array Modified data array with added this controller data.
	 */
	public function script_data( array $data ): array {
		$data['strong_password'] = $this->data_frontend();

		return $data;
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'is_active'     => $this->model->is_active(),
				'model'         => $this->model->export(),
				'all_roles'     => wp_list_pluck( get_editable_roles(), 'name' ),
				'woo_forms'     => Woocommerce::get_forms(),
				'is_woo_active' => wd_di()->get( Woocommerce::class )->is_activated(),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$model_data = $request->get_data_by_model( $this->model );
		$this->model->import( $model_data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			$response = array(
				'auto_close' => true,
			);
			if ( $this->model->enabled && array() === $this->model->user_roles ) {
				// we need to control this message in front.
				$response['warning'] = sprintf(
					/* translators: 1. Open tag. 2. Close tag. */
					esc_html__( 'You need to check at least one of the %1$sStrong Passwords checks preferences below%2$s and save your settings to enable Strong Password Protection.', 'wpdef' ),
					'<b>',
					'</b>'
				);

				return new Response( true, array_merge( $response, $this->data_frontend() ) );
			}

			if ( $this->maybe_track() ) {
				$prev_data = $this->model->get_old_settings();
				if ( array() !== $prev_data ) {
					if ( $this->model->enabled && ! $prev_data['enabled'] ) {
						$need_track = true;
						$event      = 'def_feature_activated';
					} elseif ( ! $this->model->enabled && $prev_data['enabled'] ) {
						$need_track = true;
						$event      = 'def_feature_deactivated';
					} else {
						$need_track = false;
					}

					if ( $need_track ) {
						$data = array(
							'Feature'        => 'Strong Password',
							'Triggered From' => 'Feature page',
						);
						$this->track_feature( $event, $data );
					}
				}
			}

			return new Response( true, array_merge( $response, $this->data_frontend() ) );
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
		);
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 *
	 * @return array
	 */
	public function to_array() {
		return array();
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data Data from other source.
	 *
	 * @return null|void
	 */
	public function import_data( array $data ) {
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
		}
	}

	/**
	 * Remove all settings, configs generated in this container runtime.
	 *
	 * @return mixed
	 */
	public function remove_settings() {
	}

	/**
	 * Remove all data.
	 */
	public function remove_data() {}

	/**
	 * Export strings.
	 *
	 * @return array
	 */
	public function export_strings(): array {
		return array(
			$this->model->is_active() ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Provides data for the dashboard widget.
	 *
	 * @return array An array of dashboard widget data.
	 */
	public function dashboard_widget(): array {
		return array( 'model' => $this->model->export() );
	}
}