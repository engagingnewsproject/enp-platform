<?php
/**
 * Handles firewall logs and interactions with Block list API service.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use DateTime;
use Exception;
use Countable;
use Valitron\Validator;
use Calotes\Helper\HTTP;
use WP_Defender\Controller;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Formats;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Component\User_Agent;
use WP_Defender\Component\IP\Global_IP;
use WP_Defender\Component\Table_Lockout;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Integrations\Antibot_Global_Firewall_Client;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Component\Firewall_Logs as Firewall_Logs_Component;

/**
 * Responsible for managing firewall logs, including bulk actions, exporting logs to CSV,
 *  toggling IP addresses and user agents, querying logs, and sending logs to the Block list API.
 */
class Firewall_Logs extends Controller {

	use Formats;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * The WPMUDEV instance used for interacting with WPMUDEV services.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * The client for interacting with the AntiBot Global Firewall API.
	 *
	 * @var Antibot_Global_Firewall_Client
	 */
	private $antibot_client;

	/**
	 * The transient key used to store the list of IP addresses from the
	 * Akismet service that are blocked in the firewall.
	 *
	 * @var string
	 */
	const AKISMET_BLOCKED_IPS = 'defender_akismet_blocked_ips';

	/**
	 * Constructor for the class.
	 *
	 * @param  Antibot_Global_Firewall_Client $antibot_client  The client for interacting with the Block list API service.
	 */
	public function __construct( Antibot_Global_Firewall_Client $antibot_client ) {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );

		$this->wpmudev = wd_di()->get( WPMUDEV::class );

		$this->antibot_client = $antibot_client;

		/**
		 * Send Firewall logs to AntiBot Global Firewall API.
		 *
		 * @var Network_Cron_Manager $network_cron_manager
		 */
		$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
		$network_cron_manager->register_callback(
			'wpdef_firewall_send_compact_logs_to_api',
			array( $this, 'send_compact_logs_to_api' ),
			12 * HOUR_IN_SECONDS,
			time() + 15
		);
		if ( class_exists( 'Akismet' ) ) {
			add_filter( 'http_response', array( $this, 'akismet_http_response' ), 10, 3 );
		}
	}

	/**
	 * Bulk action handler for lockout logs.
	 *
	 * @param  Request $request  The request object containing the data.
	 *
	 * @return Response The response object with the result of the bulk action.
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
		$logs = array();
		if ( is_array( $ids ) || $ids instanceof Countable ? count( $ids ) : 0 ) {
			foreach ( $ids as $id ) {
				$model = Lockout_Log::find_by_id( $id );
				if ( is_object( $model ) ) {
					$bl = wd_di()->get( Blacklist_Lockout::class );
					switch ( $data['action'] ) {
						case 'ban':
							$bl->remove_from_list( $model->ip, 'allowlist' );
							$bl->add_to_list( $model->ip, 'blocklist' );
							$ips[ $model->ip ] = $model->ip;
							$logs[]            = $model;
							break;
						case 'allowlist':
							$bl->remove_from_list( $model->ip, 'blocklist' );
							$bl->add_to_list( $model->ip, 'allowlist' );
							$ips[ $model->ip ] = $model->ip;
							$logs[]            = $model;
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

		if ( count( $logs ) > 0 ) {
			$logs = Lockout_Log::format_logs( $logs );
		}

		switch ( $data['action'] ) {
			case 'allowlist':
				$messages = sprintf(
				/* translators: 1: IP Address(es). 2: URL for Defender > Firewall > IP Banning. */
					esc_html__(
						'IP %1$s has been added to your allowlist. You can control your allowlist in %2$s.',
						'wpdef'
					),
					implode( ', ', $ips ),
					'<a href="' . network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist#tab-ip-allowlist' ) . '">' . esc_html__( 'IP Lockouts', 'wpdef' ) . '</a>'
				);
				break;
			case 'ban':
				$messages = sprintf(
				/* translators: 1: IP Address(es). 2: URL for Defender > Firewall > IP Banning. */
					esc_html__(
						'IP %1$s has been added to your blocklist. You can control your blocklist in %2$s.',
						'wpdef'
					),
					implode( ', ', $ips ),
					'<a href="' . network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' ) . '">' . esc_html__( 'IP Lockouts', 'wpdef' ) . '</a>'
				);
				break;
			case 'delete':
				$messages = sprintf(
				/* translators: %s: IP Address(es) */
					esc_html__( 'IP %s has been deleted', 'wpdef' ),
					implode( ', ', $ips )
				);
				break;
			default:
				$messages = '';
				break;
		}

		return new Response(
			true,
			array(
				'message' => $messages,
				'logs'    => $logs,
			)
		);
	}

	/**
	 * Export logs to CSV
	 *
	 * @return void
	 * @defender_route
	 * @throws Exception On failure.
	 */
	public function export_as_csv(): void {
		$date_from = HTTP::get( 'date_from', strtotime( '-7 days midnight' ) );
		$date_to   = HTTP::get( 'date_to', strtotime( 'tomorrow' ) );
		// Convert date using timezone.
		$timezone  = wp_timezone();
		$date_from = ( new DateTime( $date_from, $timezone ) )->setTime( 0, 0, 0 )->getTimestamp();
		$date_to   = ( new DateTime( $date_to, $timezone ) )->setTime( 23, 59, 59 )->getTimestamp();
		$filters   = array(
			'from'       => $date_from,
			'to'         => $date_to,
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
		$per_page = (int) defender_get_data_from_request( 'per_page', 'g' );
		if ( 0 === $per_page ) {
			$per_page = 20;
		}
		if ( - 1 === $per_page ) {
			$per_page = false;
		}

		$paged = (int) defender_get_data_from_request( 'paged', 'g' );
		if ( 0 === $paged ) {
			$paged = 1;
		}
		$logs = Lockout_Log::query_logs( $filters, $paged, 'id', 'desc', $per_page );

		$tl_component = new Table_Lockout();

		$ua_component = wd_di()->get( User_Agent::class );

		$filename = 'wdf-lockout-logs-export-' . wp_date( 'ymdHis' ) . '.csv';

		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		extension_loaded( 'zlib' ) ? ob_start( 'ob_gzhandler' ) : ob_start();

		$fp      = fopen( 'php://output', 'w' );
		$headers = array(
			esc_html__( 'Log', 'wpdef' ),
			esc_html__( 'Date / Time', 'wpdef' ),
			esc_html__( 'Type', 'wpdef' ),
			esc_html__( 'IP address', 'wpdef' ),
			esc_html__( 'IP Status', 'wpdef' ),
			esc_html__( 'User Agent Name', 'wpdef' ),
			esc_html__( 'User Agent Status', 'wpdef' ),
		);
		fputcsv( $fp, $headers, ',', '"', '\\' );

		$flush_limit = Lockout_Log::INFINITE_SCROLL_SIZE;
		foreach ( $logs as $key => $log ) {
			$item = array(
				$log->log,
				$this->format_date_time( $log->date ),
				$tl_component->get_type( $log->type ),
				$log->ip,
				$tl_component->get_ip_status_text( $log->ip ),
				$log->user_agent,
				$ua_component->get_status_text( $log->type, $log->tried ),
			);
			fputcsv( $fp, $item, ',', '"', '\\' );

			if ( 0 === $key % $flush_limit ) {
				ob_flush();
				flush();
			}
		}
		// WP_Filesystem is not suitable here because it abstracts to reading/writing files on disk, not to output streams.
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit();
	}

	/**
	 * Toggles an IP address to or from a specified list.
	 *
	 * @param  Request $request  The HTTP request object.
	 *
	 * @return Response The HTTP response object.
	 * @defender_route
	 */
	public function toggle_ip_to_list( Request $request ): Response {
		$data = $request->get_data(
			array(
				'ip'         => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'list'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ban_status' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$ip         = $data['ip'];
		$collection = $data['list'];

		$model = wd_di()->get( Blacklist_Lockout::class );
		if ( $model->is_ip_in_list( $ip, $collection ) ) {
			$model->remove_from_list( $ip, $collection );
			/* translators: 1: IP address, 2: IP address list, 3: IP address list, 4: URL for Defender > Firewall > IP Banning. */
			$message = esc_html__(
				'IP %1$s has been removed from your %2$s. You can control your %3$s in %4$s.',
				'wpdef'
			);
		} else {
			$model->add_to_list( $ip, $collection );

			$global_ip_service = wd_di()->get( Global_IP::class );
			if ( $global_ip_service->can_blocklist_autosync() ) {
				$global_ip_data = array(
					'block_list' => array( $ip ),
				);
				$global_ip_service->add_to_global_ip_list( $global_ip_data );
			}

			/* translators: 1: IP address. 2: IP address list. 3: IP address list. 4: URL for Defender > Firewall > IP Banning. */
			$message = esc_html__(
				'IP %1$s has been added to your %2$s. You can control your %3$s in %4$s.',
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
					$data['ip'] ?? '-',
					$data['list'],
					$data['list'],
					'<a href="' . network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' ) . '">' . esc_html__( 'IP Lockouts', 'wpdef' ) . '</a>'
				),
				'logs'    => $logs,
			)
		);
	}

	/**
	 * Toggles a user agent to/from a specified list based on the given request data.
	 *
	 * @param Request $request  The request object containing the data for toggling the user agent.
	 *
	 * @return Response The response object indicating the success or failure of the toggle operation.
	 * @defender_route
	 */
	public function toggle_ua_to_list( Request $request ): Response {
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

		$ua         = $data['ua'];
		$collection = $data['list'];
		$action     = $data['scenario'];

		$model = wd_di()->get( User_Agent_Lockout::class );

		if ( 'remove' === $action && $model->is_ua_in_list( $ua, $collection ) ) {
			$model->remove_from_list( $ua, $collection );
			/* translators: 1: User agent. 2: User agent list. 3: User agent list. 4: URL for Defender > Firewall > User Agent Banning. */
			$message = esc_html__(
				'User agent %1$s has been removed from your %2$s. You can control your %3$s in %4$s.',
				'wpdef'
			);
		} elseif ( 'add' === $action ) {

			/**
			 * Possible scenario on regex blocklist. For e.g. UA term `run` present in allowlist & `r.n` regex in blocklist then remove `run` to block `run` user agent using regex `r.n`.
			 */
			if ( 'blocklist' === $collection && $model->is_ua_in_list( $ua, 'allowlist' ) ) {
				$model->remove_from_list( $ua, 'allowlist' );
			}

			if ( ! $model->is_ua_in_list( $ua, $collection ) ) {
				$model->add_to_list( $ua, $collection );
			}
			/* translators: 1: User agent. 2: User agent list. 3: User agent list. 4: URL for Defender > Firewall > User Agent Banning. */
			$message = esc_html__(
				'User agent %1$s has been added to your %2$s. You can control your %3$s in %4$s.',
				'wpdef'
			);
		} else {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Wrong result.', 'wpdef' ) )
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
				'message'                 => sprintf(
					$message,
					'<strong>' . $data['ua'] . '</strong>',
					$data['list'],
					$data['list'],
					'<a href="' . network_admin_url( 'admin.php?page=wdf-ip-lockout&view=ua-lockout' ) . '">' . esc_html__( 'User Agent Banning', 'wpdef' ) . '</a>'
				),
				'logs'                    => $logs,
				// Include blocklist preset values for the frontend.
				'blocklist_preset_values' => $model->blocklist_preset_values,
			)
		);
	}

	/**
	 * Query the logs and display on frontend.
	 *
	 * @param Request $request  The request object containing filter parameters.
	 *
	 * @return Response
	 * @defender_route
	 * @throws Exception If an argument is not of the expected type.
	 */
	public function query_logs( Request $request ): Response {
		$data = $request->get_data(
			array(
				'date_from'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'date_to'    => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ip'         => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'      => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'sort'       => array(
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
			return new Response( false, array( 'message' => esc_html__( 'Wrong start and end date.', 'wpdef' ) ) );
		}
		$sort = $data['sort'] ?? Table_Lockout::SORT_DESC;
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
		// Convert date using timezone.
		$timezone  = wp_timezone();
		$date_from = ( new DateTime( $data['date_from'], $timezone ) )
			->setTime( 0, 0, 0 )
			->getTimestamp();
		$date_to   = ( new DateTime( $data['date_to'], $timezone ) )
			->setTime( 23, 59, 59 )
			->getTimestamp();

		$result = $this->retrieve_logs(
			array(
				'from'       => $date_from,
				'to'         => $date_to,
				'ip'         => $data['ip'],
				// If this is all, then we set to null to exclude it from the filter.
				'type'       => 'all' === $data['type'] ? '' : $data['type'],
				'ban_status' => 'all' === $data['ban_status'] ? '' : $data['ban_status'],
			),
			$data['paged'],
			$order,
			$order_by
		);

		return new Response( true, $result );
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script(
			'def-iplockout',
			'lockout_logs',
			array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() )
		);
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$type = defender_get_data_from_request( 'type', 'g' );

		$def_filters  = array( 'misc' => wd_di()->get( Table_Lockout::class )->get_filters() );
		$init_filters = array(
			'from'       => strtotime( '-30 days' ),
			'to'         => time(),
			'type'       => $type,
			'ip'         => '',
			'ban_status' => '',
		);

		return array_merge( $this->retrieve_logs( $init_filters, 1 ), $def_filters );
	}

	/**
	 * Retrieves logs based on the given filters, paging, order, and order by.
	 *
	 * @param  array  $filters  An array containing the following keys:
	 *                               - 'from': The start date of the logs.
	 *                               - 'to': The end date of the logs.
	 *                               - 'type': The type of logs.
	 *                               - 'ip': The IP address of the logs.
	 *                               - 'ban_status': The ban status of the logs.
	 * @param  int    $paged  The page number of the logs to retrieve. Default is 1.
	 * @param  string $order  The order of the logs. Default is 'desc'.
	 * @param  string $order_by  The field to order the logs by. Default is 'id'.
	 *
	 * @return array An array containing the following keys:
	 *               - 'count': The total count of logs.
	 *               - 'logs': The retrieved logs.
	 *               - 'per_page': The number of logs per page.
	 *               - 'total_pages': The total number of pages.
	 */
	private function retrieve_logs( $filters, $paged = 1, $order = 'desc', $order_by = 'id' ): array {
		// User can set the number of logs to retrieve per page.
		$per_page = (int) defender_get_data_from_request( 'per_page', 'p' );
		if ( 0 === $per_page ) {
			$per_page = 20;
		}
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
		delete_site_transient( self::AKISMET_BLOCKED_IPS );
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Exports strings.
	 *
	 * @param array $logs Prepared logs.
	 */
	private function maybe_send_reports( array $logs ): void {
		$offset     = 0;
		$length     = 1000;
		$logs_chunk = array_slice( $logs, $offset, $length );
		while ( ! empty( $logs_chunk ) ) {
			$data = array(
				'logs' => $logs_chunk,
			);

			$response = $this->antibot_client->send_reports( $data );

			if ( is_wp_error( $response ) ) {
				$this->log(
					sprintf( 'AntiBot Global Firewall Error: %s', $response->get_error_message() ),
					Firewall::FIREWALL_LOG
				);
			} elseif ( isset( $response['status'] ) && 'error' === $response['status'] ) {
				$this->log(
					sprintf( 'AntiBot Global Firewall Error: %s', $response['message'] ),
					Firewall::FIREWALL_LOG
				);
			}

			$offset    += $length;
			$logs_chunk = array_slice( $logs, $offset, $length );
		}

		$this->log( 'AntiBot Global Firewall: Process for sending logs completed.', Firewall::FIREWALL_LOG );
	}

	/**
	 * Send last 12 hours logs to AntiBot Global Firewall API.
	 * If running for first time then grab 7 days of logs.
	 * If last run difference is greater than 12 hours then grab 12+ hours of log but at most grab 7 days of logs.
	 *
	 * @return void
	 */
	public function send_compact_logs_to_api(): void {
		$site_id    = get_current_blog_id();
		$event_name = 'wpdef_firewall_send_compact_logs_to_api';
		$this->log( "Cron job {$event_name} triggered at site {$site_id}", Firewall::FIREWALL_LOG );

		/**
		 * Enable/disable sending Firewall logs to API.
		 *
		 * @param bool  $status  Status for sending logs. Send logs to API if true.
		 *
		 * @since 4.5.0
		 */
		$send_logs = (bool) apply_filters( 'wpdef_firewall_send_logs_to_api', true );

		if (
			! $send_logs ||
			! $this->wpmudev->is_dash_activated() ||
			! $this->wpmudev->is_site_connected_to_hub()
		) {
			return;
		}

		// Acquire lock before executing.
		if ( ! $this->acquire_cron_lock( $event_name, 'twicedaily' ) ) {
			$this->log( "{$event_name} is skipped running from site {$site_id}", Firewall::FIREWALL_LOG );
			return;
		}
		// Log the site ID where the event is triggered.
		$this->log( "{$event_name} is processing from site {$site_id}", Firewall::FIREWALL_LOG );
		$from = time() - ( 7 * DAY_IN_SECONDS );

		$last_run_time = get_site_option( 'wpdef_ip_blocklist_sync_last_run_time' );
		if ( $last_run_time ) {
			$time_difference = time() - $last_run_time;

			if ( $time_difference < 7 * DAY_IN_SECONDS ) { // 7 days in seconds
				$from = $last_run_time;
			}
		}
		update_site_option( 'wpdef_ip_blocklist_sync_last_run_time', time() );

		$service = wd_di()->get( Firewall_Logs_Component::class );
		$logs    = $service->get_compact_logs( $from );

		if ( ! empty( $logs ) ) {
			$this->maybe_send_reports( $logs );
		}

		$logs = $service->get_akismet_auto_spam_comment_logs();
		if ( ! empty( $logs ) ) {
			$this->maybe_send_reports( $logs );
		}

		// Release lock after execution.
		$this->release_cron_lock( $event_name );
	}

	/**
	 * Filters a successful HTTP API response before returning it.
	 *
	 * @param array  $response    HTTP response.
	 * @param array  $parsed_args HTTP request arguments.
	 * @param string $url         The request URL.
	 *
	 * @return array HTTP response.
	 */
	public function akismet_http_response( $response, $parsed_args, $url ) {
		// If the URL is not the Akismet comment-check endpoint, return the response as is.
		if ( 'https://rest.akismet.com/1.1/comment-check' !== $url ) {
			return $response;
		}

		// Retrieve response body safely.
		$body = wp_remote_retrieve_body( $response );
		// If the body is empty or does not equal 'true' (indicating spam), return the response as is.
		if ( empty( $body ) || trim( $body ) !== 'true' ) {
			return $response;
		}

		// Ensure the request body contains data; otherwise, return the response.
		if ( empty( $parsed_args['body'] ) ) {
			return $response;
		}

		$request_data = wp_parse_args( $parsed_args['body'] );
		// If the comment author's IP is not present in the request data, return the response.
		if ( empty( $request_data['comment_author_IP'] ) ) {
			return $response;
		}

		// Validate the user IP address from the request data.
		$user_ip = filter_var( $request_data['comment_author_IP'], FILTER_VALIDATE_IP );
		if ( false === $user_ip ) {
			return $response;
		}

		// Retrieve the current list of blocked IPs from the site transient.
		$option = get_site_transient( self::AKISMET_BLOCKED_IPS );
		// Ensure the retrieved data is an array; if not, initialize it as an empty array.
		if ( ! is_array( $option ) ) {
			$option = array();
		}

		// Increment the count of how many times this IP has been associated with spam.
		$option[ $user_ip ] = isset( $option[ $user_ip ] ) ? (int) $option[ $user_ip ] + 1 : 1;
		// Update the site transient with the new list of blocked IPs.
		set_site_transient( self::AKISMET_BLOCKED_IPS, $option );

		// Return the original HTTP response.
		return $response;
	}
}