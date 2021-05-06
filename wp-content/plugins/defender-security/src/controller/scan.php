<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Controller2;
use WP_Defender\Model\Notification\Malware_Report;
use Valitron\Validator;

class Scan extends Controller2 {
	protected $slug = 'wdf-scan';

	/**
	 * @var \WP_Defender\Model\Setting\Scan;
	 */
	protected $model;

	/**
	 * @var \WP_Defender\Component\Scan
	 */
	protected $service;

	/**
	 * Scan constructor.
	 */
	public function __construct() {
		$this->register_page(
			esc_html__( 'Malware Scanning', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		$this->model   = new \WP_Defender\Model\Setting\Scan();
		$this->service = new \WP_Defender\Component\Scan();
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_defender_process_scan', array( &$this, 'process' ) );
		add_action( 'wp_ajax_nopriv_defender_process_scan', array( &$this, 'process' ) );
		//Clean up data after successful core update
		add_action( '_core_updated_successfully', array( &$this, 'clean_up_data' ) );
	}

	/**
	 * Clean up data after core updating
	 */
	public function clean_up_data() {
		$this->service->clean_up();
	}

	/**
	 * Start a scan
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function start( Request $request ) {
		$model = \WP_Defender\Model\Scan::create();
		if ( is_object( $model ) && ! is_wp_error( $model ) ) {
			$this->log( 'Initial ping self', 'scan' );
			$this->self_ping();

			return new Response(
				true,
				array(
					'status'      => $model->status,
					'status_text' => $model->get_status_text(),
					'percent'     => 0,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => __( 'A scan is already in progress', 'wpdef' ),
			)
		);
	}

	/**
	 * Use this for self ping, so it can both run in background and active mode
	 * with good performance
	 *
	 * @return bool
	 * @throws \ReflectionException
	 * @defender_route
	 * @is_public
	 */
	public function process() {
		if ( $this->service->has_lock() ) {
			$this->log( 'Fallback as already a process is running', 'scan' );

			return;
		}

		//this is create file lock, for make sure only 1 process run as a time
		$this->service->create_lock();
		//Check if the ping is from self or not
		$ret = $this->service->process();
		$this->log( 'process done, queue for next', 'scan' );
		if ( false === $ret ) {
			//ping self
			$this->log( 'Scan not done, pinging', 'scan' );
			$this->service->remove_lock();
			$this->self_ping();
		} else {
			$this->service->remove_lock();
		}
	}

	/**
	 * Query the status of
	 *
	 * @return Response
	 * @defender_route
	 */
	public function status() {
		$scan = \WP_Defender\Model\Scan::get_active();
		if ( is_object( $scan ) ) {
			return new Response( false, $scan->to_array() );
		}
		$scan = \WP_Defender\Model\Scan::get_last();
		if ( is_object( $scan ) && ! is_wp_error( $scan ) ) {
			return new Response( true, $scan->to_array() );
		}
		return new Response(
			false,
			array(
				'message' => __( 'Error during scanning', 'wpdef' ),
			)
		);
	}

	/**
	 * Cancel current scan
	 *
	 * @return Response
	 * @defender_route
	 */
	public function cancel() {
		$component = new \WP_Defender\Component\Scan();
		$component->cancel_a_scan();
		$last = \WP_Defender\Model\Scan::get_last();
		if ( is_object( $last ) && ! is_wp_error( $last ) ) {
			$last = $last->to_array();
		}

		return new Response(
			true,
			array(
				'scan' => $last,
			)
		);
	}

	/**
	 * A central controller to pass any request from frontend to scan item
	 * @return Response
	 * @defender_route
	 */
	public function item_hub( Request $request ) {
		$data      = $request->get_data(
			array(
				'id'        => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'intention' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$id        = isset( $data['id'] ) ? $data['id'] : false;
		$intention = isset( $data['intention'] ) ? $data['intention'] : false;
		if ( false === $id || false === $intention || ! in_array(
				$intention,
				array(
					'pull_src',
					'resolve',
					'ignore',
					'delete',
					'unignore',
				)
			) ) {
			wp_die();
		}

		$scan = \WP_Defender\Model\Scan::get_last();
		$item = $scan->get_issue( $id );
		if ( is_object( $item ) && $item->has_method( $intention ) ) {
			$result = $item->$intention();
			if ( is_wp_error( $result ) ) {
				return new Response(
					false,
					array(
						'message' => $result->get_error_message(),
					)
				);
			}
			//refresh scan instance
			$scan           = \WP_Defender\Model\Scan::get_last();
			$result['scan'] = $scan->to_array();

			return new Response( true, $result );
		}

		return new Response( false, array() );
	}

	/**
	 * Process for bulk action
	 *
	 * @defender_route
	 * @return Response
	 */
	public function bulk_hub( Request $request ) {
		$data      = $request->get_data(
			array(
				'items' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
				'bulk'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$items     = isset( $data['items'] ) ? $data['items'] : array();
		$intention = isset( $data['bulk'] ) ? $data['bulk'] : false;

		if (
			empty( $items )
			|| ! is_array( $items )
			|| false === $intention
			|| ! in_array( $intention, array( 'ignore', 'unignore', 'delete' ), true )
		) {
			return new Response( false, array() );
		}
		$scan = \WP_Defender\Model\Scan::get_last();
		if ( ! is_object( $scan ) ) {
			return new Response( false, array() );
		}

		foreach ( $items as $id ) {
			if ( 'ignore' === $intention ) {
				$scan->ignore_issue( (int) $id );
			} elseif ( 'unignore' === $intention ) {
				$scan->unignore_issue( (int) $id );
			} elseif ( 'delete' === $intention ) {
				$item = $scan->get_issue( (int) $id );
				if ( is_object( $item ) ) {
					$item->delete();
				}
			}
		}

		return new Response(
			true,
			array(
				'scan' => $scan->to_array(),
			)
		);
	}

	/**
	 * Endpoint for saving data
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data_by_model( $this->model );
		//Case#1: enable all child options, if parent and all child options are disabled, so that there is no notice when saving
		if (
			! $data['integrity_check']
			&& ! $data['check_core']
			&& ! $data['check_themes']
			&& ! $data['check_plugins']
		) {
			$data['check_core']    = true;
			$data['check_themes']  = true;
			$data['check_plugins'] = true;
		}

		//Case#2: Suspicious code is activated BUT File change detection is deactivated then show the notice
		if ( $data['scan_malware'] && ! $data['integrity_check'] ){
			$response = array(
				'type_notice' => 'info',
				'message'     => __( "To reduce false-positive results, we recommend enabling" .
					" <strong>File change detection</strong> options for all scan types while the" .
					" <strong>Suspicious code</strong> option is enabled.", 'wpdef' ),
			);
		} else {
			//Prepare response message for usual successful case
			$response = array(
				'message' => __( 'Your settings have been updated.', 'wpdef' ),
			);
		}

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();

			return new Response(
				true,
				$response
			);
		} else {
			return new Response(
				false,
				array(
					'message' => $this->model->get_formatted_errors(),
				)
			);
		}
	}

	/**
	 * Get the issues mainly for pagination request
	 * 
	 * @return Response
	 * @defender_route
	 */
	public function get_issues( Request $request ) {
		$data      = $request->get_data(
			array(
				'scenario' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'per_page'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'  	=> array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		// Validate the request
		$v = new Validator( $data, array() );
		$v->rule( 'required', array( 'scenario', 'type', 'per_page', 'paged' ) );
		if ( ! $v->validate() ) {
			return new Response(
				false,
				array(
					'message' => '',
				)
			);
		}

		$scan = \WP_Defender\Model\Scan::get_last();
		$issues = $scan->to_array( $data['per_page'], $data['paged'], $data['type'] );

		return new Response(
			true,
			array(
				'issue' => $issues['issues_items'],
				'ignored' => $issues['ignored_items'],
				'paging' => $issues['paging'],
				'count' => $issues['count'],
			)
		);
	}

	/**
	 * Self ping site for the process can run in background
	 */
	public function self_ping() {
		$url = admin_url( 'admin-ajax.php' );
		$url = add_query_arg(
			array(
				'action' => 'defender_process_scan',
			),
			$url
		);
		$this->log( sprintf( 'ping url %s', $url ), 'scan' );
		$body = array(
			'body'     => array(),
			'blocking' => false,
			'timeout'  => 3,
			'headers'  => array(
				'user-agent' => sprintf(
					'Mozilla/5.0 (compatible; WPMU DEV Defender/%1$s; +https://wpmudev.com)',
					DEFENDER_VERSION
				),
			),
		);
		if (
			isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['HTTP_AUTHORIZATION'] )
			&& ! empty( $_SERVER['PHP_AUTH_USER'] )
			&& ! empty( $_SERVER['HTTP_AUTHORIZATION'] )
		) {
			$body['headers']['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
		}

		wp_remote_post( $url, $body );
	}

	/**
	 * Render main page
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Enqueue assets
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-scan', 'scan', $this->data_frontend() );
		wp_enqueue_script( 'def-scan' );
		wp_enqueue_script( 'clipboard' );
		$this->enqueue_main_assets();
		wp_enqueue_script( 'def-codemirror', defender_asset_url( '/assets/js/vendor/codemirror/codemirror.js' ) );
		wp_enqueue_script( 'def-codemirror-xml', defender_asset_url( '/assets/js/vendor/codemirror/xml/xml.js' ) );
		wp_enqueue_script(
			'def-codemirror-clike',
			defender_asset_url( '/assets/js/vendor/codemirror/clike/clike.js' )
		);
		wp_enqueue_script( 'def-codemirror-css', defender_asset_url( '/assets/js/vendor/codemirror/css/css.js' ) );
		wp_enqueue_script(
			'def-codemirror-javascript',
			defender_asset_url( '/assets/js/vendor/codemirror/javascript/javascript.js' )
		);
		wp_enqueue_script(
			'def-codemirror-htmlmixed',
			defender_asset_url( '/assets/js/vendor/codemirror/htmlmixed/htmlmixed.js' )
		);
		wp_enqueue_script( 'def-codemirror-php', defender_asset_url( '/assets/js/vendor/codemirror/php/php.js' ) );
		wp_enqueue_script(
			'def-codemirror-merge',
			defender_asset_url( '/assets/js/vendor/codemirror/merge/merge.js' )
		);
		wp_enqueue_script( 'def-diff-match-patch', defender_asset_url( '/assets/js/vendor/diff-match-patch.js' ) );
		wp_enqueue_script(
			'def-codemirror-annotatescrollbar',
			defender_asset_url( '/assets/js/vendor/codemirror/scroll/annotatescrollbar.js' )
		);
		wp_enqueue_script(
			'def-codemirror-simplescrollbars',
			defender_asset_url( '/assets/js/vendor/codemirror/scroll/simplescrollbars.js' )
		);
		wp_enqueue_script(
			'def-codemirror-searchcursor',
			defender_asset_url( '/assets/js/vendor/codemirror/search/searchcursor.js' )
		);
		wp_enqueue_script(
			'def-codemirror-matchonscrollbars',
			defender_asset_url( '/assets/js/vendor/codemirror/search/matchesonscrollbar.js' )
		);

		wp_enqueue_style( 'def-codemirror', defender_asset_url( '/assets/js/vendor/codemirror/codemirror.css' ) );
		wp_enqueue_style( 'def-codemirror-dracula', defender_asset_url( '/assets/js/vendor/codemirror/dracula.css' ) );
		wp_enqueue_style(
			'def-codemirror-merge',
			defender_asset_url( '/assets/js/vendor/codemirror/merge/merge.css' )
		);
		wp_enqueue_style(
			'def-codemirror-matchonscrollbars',
			defender_asset_url( '/assets/js/vendor/codemirror/search/matchesonscrollbar.css' )
		);
		wp_enqueue_style(
			'def-codemirror-simplescrollbars',
			defender_asset_url( '/assets/js/vendor/codemirror/scroll/simplescrollbars.css' )
		);
	}

	/**
	 * @return array[]
	 */
	public function to_array() {
		$scan = \WP_Defender\Model\Scan::get_active();
		$last = \WP_Defender\Model\Scan::get_last();
		if ( ! is_object( $scan ) && ! is_object( $last ) ) {
			$scan = null;
		} else {
			$scan = is_object( $scan ) ? $scan->to_array() : $last->to_array();
		}

		return array_merge(
			array(
				'scan'   => $scan,
				'report' => array(
					'enabled'   => true,
					'frequency' => 'weekly',
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Scan() )->delete();
	}

	public function remove_data() {}

	/**
	 * This should setup the optimize configs for this module
	 */
	public function optimize_configs() {
		$settings = new \WP_Defender\Model\Setting\Scan();
		$settings->save();
		//schedule it
		$report = new Malware_Report();
		$report->save();
	}

	/**
	 * @return array
	 */
	public function data_frontend() {
		$scan     = \WP_Defender\Model\Scan::get_active();
		$last     = \WP_Defender\Model\Scan::get_last();
		$per_page = 10;
		$paged    = 1;
		if ( ! is_object( $scan ) && ! is_object( $last ) ) {
			$scan = null;
		} else {
			$scan = is_object( $scan ) ? $scan->to_array( $per_page, $paged ) : $last->to_array( $per_page, $paged );
		}
		$settings    = new \WP_Defender\Model\Setting\Scan();
		$report      = wd_di()->get( Malware_Report::class );
		$report_text = __( 'Automatic scans are disabled', 'wpdef' );
		if ( Malware_Report::STATUS_ACTIVE === $report->status ) {
			$report_text = sprintf( __( 'Automatic scans are running %s', 'wpdef' ), $report->frequency );
		}
		$data = array(
			'scan'         => $scan,
			'settings'     => $settings->export(),
			'report'       => $report_text,
			'notification' => $report->to_string(),
			'next_run'     => $report->get_next_run_as_string(),
		);

		return array_merge( $data, $this->dump_routes_and_nonces() );
	}

	public function import_data( $data ) {
		$model = $this->model;

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @param bool $is_pro
	 *
	 * @return bool
	 */
	private function is_any_active( $is_pro ) {
		$settings = new \WP_Defender\Model\Setting\Scan();
		$integrity_check = $settings->is_any_filetypes_checked();
		if ( ! $integrity_check && ! $is_pro ) {
			return false;
		} elseif (
			( ! $integrity_check && ! $settings->check_known_vuln && ! $settings->scan_malware )
			&& ! $is_pro
		) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		$strings = array();
		$is_pro  = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
		if ( $this->is_any_active( $is_pro ) ) {
			$strings[] = __( 'Active', 'wpdef' );
		} else {
			$strings[] = __( 'Inactive', 'wpdef' );
		}

		$scan_report       = new \WP_Defender\Model\Notification\Malware_Report();
		$scan_notification = new \WP_Defender\Model\Notification\Malware_Notification();
		if ( 'enabled' === $scan_notification->status ) {
			$strings[] = __( 'Email notifications active', 'wpdef' );
		}
		if ( $is_pro && 'enabled' === $scan_report->status ) {
			$strings[] = sprintf(
				__( 'Email reports sending %s', 'wpdef' ),
				$scan_report->frequency
			);
		} elseif ( ! $is_pro ) {
			$strings[] = sprintf(
				__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}
}
