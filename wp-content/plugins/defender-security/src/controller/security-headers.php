<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Notification\Tweak_Reminder;

/**
 * Class Security_Headers
 *
 * @package WP_Defender\Controller
 */
class Security_Headers extends Controller2 {
	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\Security_Headers
	 */
	public $model;

	/**
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function __construct() {
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );
		$this->model = wd_di()->get( \WP_Defender\Model\Setting\Security_Headers::class );
		$this->init_headers();
		$this->register_routes();
	}

	/**
	 * Safe way to get cached model.
	 *
	 * @return \WP_Defender\Model\Setting\Security_Headers
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Security_Headers();
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function script_data( $data ) {
		$data['security_headers'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings.
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array( 'message' => __( 'Your settings have been updated.', 'wpdef' ) ),
					$this->data_frontend()
				)
			);
		}

		return new Response( false, array( 'message' => $this->model->get_formatted_errors() ) );
	}

	/**
	 * Init headers.
	 */
	public function init_headers() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			// Refresh if on admin, on page with headers.
			if ( ( is_admin() || is_network_admin() )
				&&
				(
					( 'wdf-advanced-tools' === HTTP::get( 'page' ) )
					|| ( 'wp-defender' === HTTP::get( 'page' ) )
				)
			) {
				// This meant we don't have any data or data is overdue need to refresh list of headers.
				$this->model->refresh_headers();
			} elseif ( defined( 'DOING_CRON' ) ) {
				// If this is in cronjob, we refresh it too.
				$this->model->refresh_headers();
			}
		}

		foreach ( $this->model->get_headers() as $rule ) {
			$rule->add_hooks();
		}
	}

	public function remove_settings() {}

	public function remove_data() {}

	/**
	 * A summary data for dashboard.
	 *
	 * @return array
	 */
	public function to_array() {
		$misc = $this->get_model()->refresh_headers();

		return array_slice( $misc, 0, 3 );
	}

	/**
	 * Get data about headers.
	 *
	 * @return array
	 */
	public function get_type_headers() {
		return $this->get_model()->get_headers_by_type();
	}

	public function data_frontend() {
		$model = $this->get_model();

		return array_merge( [
			'model'   => $model->export(),
			'misc'    => $model->get_headers_as_array( true ),
			'enabled' => $model->get_enabled_headers( 3 )
		], $this->dump_routes_and_nonces() );
	}

	public function import_data( $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [
			$this->get_model()->is_any_activated() ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' )
		];
	}
}
