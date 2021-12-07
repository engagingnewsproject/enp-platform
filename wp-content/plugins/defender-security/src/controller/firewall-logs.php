<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use Valitron\Validator;
use WP_Defender\Component\Table_Lockout;
use WP_Defender\Controller2;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Traits\Formats;
use WP_Defender\Component\User_Agent;

class Firewall_Logs extends Controller2 {
	use Formats;

	/**
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function bulk( Request $request ) {
		$data = $request->get_data(
			array(
				'action' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ids'    => array(
					'type' => 'array',
				),
			)
		);
		$ids  = $data['ids'];
		$ips  = array();
		if ( count( $ids ) ) {
			foreach ( $ids as $id ) {
				$model = Lockout_Log::find_by_id( $id );
				if ( is_object( $model ) ) {
					$bl = wd_di()->get( Blacklist_Lockout::class );
					switch ( $data['action'] ) {
						case 'ban':
							$bl->remove_from_list( $model->ip, 'allowlist' );
							$bl->add_to_list( $model->ip, 'blocklist' );
							$ips[ $model->ip ] = $model->ip;
							break;
						case 'allowlist':
							$bl->remove_from_list( $model->ip, 'blocklist' );
							$bl->add_to_list( $model->ip, 'allowlist' );
							$ips[ $model->ip ] = $model->ip;
							break;
						case 'delete':
							$ips[ $model->ip ] = $model->ip;
							$model->delete();
							break;
						default:
							break;
					}
				}
			}
		}

		switch ( $data['action'] ) {
			case 'allowlist':
				$messages = sprintf(
				/* translators: ... */
					__(
						'IP %1$s has been added to your allowlist. You can control your allowlist in <a href="%2$s">IP Lockouts.</a>',
						'wpdef'
					),
					implode( ', ', $ips ),
					network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
				);
				break;
			case 'ban':
				$messages = sprintf(
				/* translators: ... */
					__(
						'IP %1$s has been added to your blocklist You can control your blocklist in <a href="%2$s">IP Lockouts.</a>',
						'wpdef'
					),
					implode( ', ', $ips ),
					network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
				);
				break;
			case 'delete':
				$messages = sprintf(
				/* translators: ... */
					__( 'IP %s has been deleted', 'wpdef' ),
					implode( ', ', $ips )
				);
				break;

		}

		return new Response(
			true,
			array(
				'message' => $messages,
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function export_as_csv( Request $request ) {
		$fp      = fopen( 'php://memory', 'w' );
		$headers = array(
			__( 'Log', 'wpdef' ),
			__( 'Date / Time', 'wpdef' ),
			__( 'Type', 'wpdef' ),
			__( 'IP address', 'wpdef' ),
			__( 'IP Status', 'wpdef' ),
			__( 'User Agent Status', 'wpdef' ),
		);
		fputcsv( $fp, $headers );

		$filters = array(
			'from'       => strtotime( 'midnight', strtotime( HTTP::get( 'date_from', strtotime( '-7 days midnight' ) ) ) ),
			'to'         => strtotime( 'tomorrow', strtotime( HTTP::get( 'date_to', strtotime( 'tomorrow' ) ) ) ),
			'type'       => HTTP::get( 'term', '' ),
			'ip'         => HTTP::get( 'ip', '' ),
			'ban_status' => HTTP::get( 'ban_status', '' ),
		);

		if ( 'all' === $filters['type'] ) {
			$filters['type'] = '';
		}

		if ( 'all' === $filters['ban_status'] ) {
			$filters['ban_status'] = '';
		}

		// User can export the number of logs that are set.
		$per_page = isset( $_GET['per_page'] ) ? sanitize_text_field( $_GET['per_page'] ) : 20;
		$paged    = isset( $_GET['paged'] ) ? sanitize_text_field( $_GET['paged'] ) : 1;
		$logs     = Lockout_Log::query_logs( $filters, $paged, 'date', 'desc', $per_page );

		$tl_component = new Table_Lockout();

		$ua_component = wd_di()->get( User_Agent::class );

		foreach ( $logs as $log ) {
			$item = array(
				$log->log,
				$this->get_date( $log->date ),
				$tl_component->get_type( $log->type ),
				$log->ip,
				$tl_component->get_ip_status_text( $log->ip ),
				$ua_component->get_status_text( $log->type, $log->tried ),
			);
			fputcsv( $fp, $item );
		}

		$filename = 'wdf-lockout-logs-export-' . gmdate( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// Make php send the generated csv lines to the browser.
		fpassthru( $fp );
		exit();
	}

	/**
	 * Get formatted date.
	 *
	 * @param $date
	 *
	 * @return string
	 */
	public function get_date( $date ) {
		if ( strtotime( '-24 hours' ) > $date ) {
			return $this->format_date_time( gmdate( 'Y-m-d H:i:s', $date ) );
		} else {
			return human_time_diff( $date, time() ) . ' ' . __( 'ago', 'wpdef' ); // phpcs:ignore
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function toggle_ip_to_list( Request $request ) {
		$data = $request->get_data(
			array(
				'ip'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'list' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ban_status' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$ip   = $data['ip'];
		$list = $data['list'];

		$model = wd_di()->get( Blacklist_Lockout::class );
		if ( $model->is_ip_in_list( $ip, $list ) ) {
			$model->remove_from_list( $ip, $list );
			/* translators: ... */
			$message = __(
				'IP %1$s has been removed from your %2$s You can control your %3$s in <a href="%4$s">IP Lockouts.</a>',
				'wpdef'
			);
		} else {
			$model->add_to_list( $ip, $list );
			/* translators: ... */
			$message = __(
				'IP %1$s has been added to your %2$s You can control your %3$s in <a href="%4$s">IP Lockouts.</a>',
				'wpdef'
			);
		}
		$filter_data = $request->get_data(
			array(
				'date_from' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'date_to'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ip_filter' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'     => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'per_page'  => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$logs        = Lockout_Log::get_logs_and_format(
			array(
				'from' => strtotime( $filter_data['date_from'] . ' 00:00:00' ),
				'to'   => strtotime( $filter_data['date_to'] . ' 23:59:59' ),
				'ip'   => $filter_data['ip_filter'],
				// If this is all, then we set to null to exclude it from the filter.
				'type' => 'all' === $filter_data['type'] ? '' : $filter_data['type'],
			),
			$filter_data['paged'],
			'id',
			'desc',
			$filter_data['per_page']
		);

		return new Response(
			true,
			array(
				'message' => sprintf(
					$message,
					$data['ip'],
					$data['list'],
					$data['list'],
					network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
				),
				'logs'    => $logs,
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function toggle_ua_to_list( Request $request ) {
		$data = $request->get_data(
			array(
				'ua'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'list'     => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'scenario' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$ua     = $data['ua'];
		$list   = $data['list'];
		$action = $data['scenario'];
		$model  = wd_di()->get( User_Agent_Lockout::class );

		if ( 'remove' === $action && $model->is_ua_in_list( $ua, $list ) ) {
			$model->remove_from_list( $ua, $list );
			/* translators: ... */
			$message = __(
				'User agent <strong>%1$s</strong> has been removed from your %2$s You can control your %3$s in <a href="%4$s">User Agent Banning.</a>',
				'wpdef'
			);
		} elseif ( 'add' === $action && ! $model->is_ua_in_list( $ua, $list ) ) {
			$model->add_to_list( $ua, $list );
			/* translators: ... */
			$message = __(
				'User agent <strong>%1$s</strong> has been added to your %2$s You can control your %3$s in <a href="%4$s">User Agent Banning.</a>',
				'wpdef'
			);
		} else {
			return new Response(
				false,
				array(
					'message' => __( 'Wrong result.', 'wpdef' ),
				)
			);
		}

		$filter_data = $request->get_data(
			array(
				'date_from' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'date_to'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ip_filter' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'     => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'per_page'  => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$logs        = Lockout_Log::get_logs_and_format(
			array(
				'from' => strtotime( $filter_data['date_from'] . ' 00:00:00' ),
				'to'   => strtotime( $filter_data['date_to'] . ' 23:59:59' ),
				'ip'   => $filter_data['ip_filter'],
				// If this is all, then we set to null to exclude it from the filter.
				'type' => 'all' === $filter_data['type'] ? '' : $filter_data['type'],
			),
			$filter_data['paged'],
			'id',
			'desc',
			$filter_data['per_page']
		);

		return new Response(
			true,
			array(
				'message' => sprintf(
					$message,
					$data['ua'],
					$data['list'],
					$data['list'],
					network_admin_url( 'admin.php?page=wdf-ip-lockout&view=ua-lockout' )
				),
				'logs'    => $logs,
			)
		);
	}

	/**
	 * Query the logs and display on frontend.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function query_logs( Request $request ) {
		$data = $request->get_data(
			array(
				'date_from' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'date_to'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ip'        => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'     => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'sort'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ban_status' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		// Validate.
		$v = new Validator( $data, array() );
		$v->rule( 'required', array( 'date_from', 'date_to' ) );
		$v->rule( 'date', array( 'date_from', 'date_to' ) );
		if ( ! $v->validate() ) {
			return new Response(
				false,
				array(
					'message' => __( 'Wrong start and end date', 'wpdef' ),
				)
			);
		}
		$sort = isset( $data['sort'] ) ? $data['sort'] : Table_Lockout::SORT_DESC;
		switch ( $sort ) {
			case 'ip':
				$order    = 'desc';
				$order_by = 'ip';
				break;
			case 'oldest':
				$order    = 'asc';
				$order_by = 'id';
				break;
			case 'user_agent':
				$order    = 'asc';
				$order_by = 'user_agent';
				break;
			default:
				$order    = 'desc';
				$order_by = 'id';
				break;
		}
		$data = $this->retrieve_logs(
			array(
				'from' => strtotime( $data['date_from'] . ' 00:00:00' ),
				'to'   => strtotime( $data['date_to'] . ' 23:59:59' ),
				'ip'   => $data['ip'],
				// If this is all, then we set to null to exclude it from the filter.
				'type' => 'all' === $data['type'] ? '' : $data['type'],
				'ban_status' => 'all' === $data['ban_status'] ? '' : $data['ban_status'],
			),
			$data['paged'],
			$order,
			$order_by
		);

		return new Response(
			true,
			$data
		);
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_enqueue_script( 'def-momentjs', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ) );
		wp_enqueue_script(
			'def-daterangepicker',
			defender_asset_url( '/assets/js/vendor/daterangepicker/daterangepicker.js' )
		);
		wp_localize_script(
			'def-iplockout',
			'lockout_logs',
			array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() )
		);
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		$def_filters  = array( 'misc' => wd_di()->get( Table_Lockout::class )->get_filters() );
		$init_filters = array(
			'from' => strtotime( '-30 days' ),
			'to'   => time(),
			'type' => '',
			'ip'   => '',
			'ban_status' => '',
		);

		return array_merge( $this->retrieve_logs( $init_filters, 1 ), $def_filters );
	}

	/**
	 * @param array  $filters
	 * @param int    $paged
	 * @param string $order
	 * @param string $order_by
	 *
	 * @return array
	 */
	private function retrieve_logs( $filters, $paged = 1, $order = 'desc', $order_by = 'id' ) {
		// User can set the number of logs to retrieve per page.
		$per_page = isset( $_POST['per_page'] ) && 0 !== (int) $_POST['per_page']
			? sanitize_text_field( $_POST['per_page'] )
			: 20;

		$conditions = array( 'ban_status' => $filters['ban_status'] );

		$count = Lockout_Log::count( $filters['from'], $filters['to'], $filters['type'], $filters['ip'], $conditions );
		$logs  = Lockout_Log::get_logs_and_format( $filters, $paged, $order_by, $order, $per_page );

		return array(
			'count'       => $count,
			'logs'        => $logs,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		);
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 */
	public function to_array() {}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data
	 */
	public function import_data( $data ) {}

	/**
	 * Remove all settings, configs generated in this container runtime.
	 */
	public function remove_settings() {}

	/**
	 * Remove all data.
	 */
	public function remove_data() {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
