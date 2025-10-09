<?php
/**
 * Handles IP and country blacklisting functionalities.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use PharData;
use Exception;
use Countable;
use WP_Defender\Traits\IP;
use WP_Defender\Controller;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Country;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Traits\Continent;
use WP_Defender\Component\Blacklist_Lockout;
use MaxMind\Db\Reader\InvalidDatabaseException;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Integrations\MaxMind_Geolocation;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;

/**
 * Handles IP and country blacklisting functionalities.
 */
class Blacklist extends Controller {

	use IP;
	use Country;
	use Continent;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * The model for handling the data.
	 *
	 * @var Model_Blacklist_Lockout
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Blacklist_Lockout
	 */
	protected $service;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Model_Blacklist_Lockout::class );
		$this->service = wd_di()->get( Blacklist_Lockout::class );
		add_action( 'wd_blacklist_this_ip', array( $this, 'blacklist_an_ip' ) );
		// Update MaxMind's DB.
		if ( ! empty( $this->model->maxmind_license_key ) ) {
			// @since 2.8.0 Allows update or remove the database of MaxMind automatic and periodically (MaxMind's TOS).
			$bind_updater = (bool) apply_filters( 'wd_update_maxmind_database', true );

			if ( $bind_updater ) {
				/**
				 * Network Cron Manager
				 *
				 * @var Network_Cron_Manager $network_cron_manager
				 */
				$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
				$network_cron_manager->register_callback(
					'wpdef_update_geoip',
					array( $this, 'update_database' ),
					WEEK_IN_SECONDS,
					'next Thursday'
				);
			}
		}
	}

	/**
	 * Add an IP into blacklist.
	 *
	 * @param  string $ip  IP address to be blacklisted.
	 *
	 * @return void
	 */
	public function blacklist_an_ip( string $ip ): void {
		$this->model->add_to_list( $ip, 'blocklist' );
		if ( defender_is_wp_org_version() ) {
			\WP_Defender\Component\Rate::run_counter_of_ip_lockouts();
		}
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 *
	 * @throws InvalidDatabaseException|Exception When unexpected data is found in the database.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'blacklist', $this->data_frontend() );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 * @throws InvalidDatabaseException|Exception When unexpected data is found in the database.
	 */
	public function data_frontend(): array {
		$user_ip     = $this->get_user_ip();
		$arr_model   = $this->model->export();
		$exist_geodb = $this->service->is_geodb_downloaded();
		// If MaxMind GeoIP DB is downloaded then display the required data.
		if ( $exist_geodb ) {
			$country_list                   = $this->countries_list();
			$blacklist_countries            = array_merge(
				array( 'all' => esc_html__( 'Block all', 'wpdef' ) ),
				$country_list
			);
			$whitelist_countries            = array_merge(
				array( 'all' => esc_html__( 'Allow all', 'wpdef' ) ),
				$country_list
			);
			$countries_with_continents_list = $this->get_countries_with_continents();
		} else {
			$blacklist_countries            = array();
			$whitelist_countries            = array();
			$countries_with_continents_list = array();
		}

		$current_country = array();
		foreach ( $user_ip as $ip ) {
			$current_country[] = $this->get_current_country( $ip );
		}

		$misc = array(
			'user_ip'                        => implode( ',', $user_ip ),
			'is_geodb_downloaded'            => $exist_geodb,
			'blacklist_countries'            => $blacklist_countries,
			'whitelist_countries'            => $whitelist_countries,
			'current_country'                => $current_country,
			'no_ips'                         => '' === $arr_model['ip_blacklist'] && '' === $arr_model['ip_whitelist'],
			'countries_with_continents_list' => $countries_with_continents_list,
			'geodb_license_key'              => $this->mask_license_key( $this->model->maxmind_license_key ),
			'module_name'                    => Model_Blacklist_Lockout::get_module_name(),
		);

		return array_merge(
			array(
				'model' => $arr_model,
				'misc'  => $misc,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Masks a license key with asterisks.
	 *
	 * @param mixed $maxmind_license_key The license key to be masked.
	 *
	 * @return string The masked license key.
	 */
	private function mask_license_key( $maxmind_license_key ): string {
		if ( ! is_string( $maxmind_license_key ) || empty( $maxmind_license_key ) ) {
			return $maxmind_license_key;
		}
		// Get the length of the license key.
		$key_length = strlen( $maxmind_license_key );
		// Decide how many characters to reveal. Revealing at least 4 characters or 25% of the key, whichever is greater.
		$reveal_chars = max( 4, intval( $key_length / 5 ) );
		// Calculate the number of asterisks to replace the hidden characters.
		$num_asterisks = $key_length - $reveal_chars;
		// Generate masked key.
		return substr( $maxmind_license_key, 0, $reveal_chars ) . str_repeat( '*', $num_asterisks );
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
		$curr_blacklist = $this->model->get_list( 'blocklist' );
		$curr_allowlist = $this->model->get_list( 'allowlist' );
		$data           = $request->get_data(
			array(
				'country_blacklist'  => array(
					'type' => 'array',
				),
				'country_whitelist'  => array(
					'type' => 'array',
				),
				'ip_blacklist'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
				'ip_whitelist'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
				'ip_lockout_message' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
				'http_ip_header'     => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'trusted_proxies_ip' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
			)
		);
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		$after_validate_blacklist = $this->model->get_list( 'blocklist' );
		$after_validate_allowlist = $this->model->get_list( 'allowlist' );
		if (
			! defender_are_arrays_equal( $curr_blacklist, $after_validate_blacklist ) ||
			! defender_are_arrays_equal( $curr_allowlist, $after_validate_allowlist )
		) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();
		}

		$this->model->import( $data );

		return new Response(
			false,
			array_merge(
				array( 'message' => $this->model->get_formatted_errors() ),
				$this->data_frontend()
			)
		);
	}

	/**
	 * Download the GEODB IP from Maxmind.
	 *
	 * @param  Request $request  The request object containing the license key.
	 *
	 * @return Response
	 * @defender_route
	 * @throws InvalidDatabaseException|Exception When unexpected data is found in the database.
	 */
	public function download_geodb( Request $request ) {
		$data        = $request->get_data(
			array(
				'license_key' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$license_key = $data['license_key'];
		$service_geo = wd_di()->get( MaxMind_Geolocation::class );
		$tmp         = $service_geo->get_downloaded_url( $license_key );
		if ( ! is_wp_error( $tmp ) ) {
			$phar = new PharData( $tmp );
			$path = $service_geo->get_db_base_path();
			if ( ! is_dir( $path ) ) {
				wp_mkdir_p( $path );
			}
			$phar->extractTo( $path, null, true );
			// Todo: move logic for the path to MaxMind_Geolocation class.
			$this->model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $service_geo->get_db_full_name();
			// Save because we'll check for a saved path.
			$this->model->save();

			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}

			foreach ( $this->get_user_ip() as $ip ) {
				$country = $this->get_current_country( $ip );
				if ( ! empty( $country ) && ! empty( $country['iso'] ) ) {
					$this->model = $this->service->add_default_whitelisted_country( $this->model, $country['iso'] );
				}
			}
			$this->model->maxmind_license_key = $license_key;
			$this->model->save();

			return new Response(
				true,
				array(
					'message'             => esc_html__(
						'You have successfully downloaded Geo IP Database. You can now use this feature to ban any countries to access any area of your website.',
						'wpdef'
					),
					'is_geodb_downloaded' => $this->service->is_geodb_downloaded(),
				)
			);
		} else {
			$this->log( 'Error from MaxMind: ' . $tmp->get_error_message(), Firewall::FIREWALL_LOG );
			$string = sprintf(
			/* translators: 1. License key with link. */
				esc_html__(
					'You have entered an invalid %1$s. If you just created the key, please wait 5 minutes before trying to activate it.',
					'wpdef'
				),
				'<a target="_blank" href="https://www.maxmind.com/en/accounts/current/license-key">' . esc_html__( 'license key', 'wpdef' ) . '</a>'
			);

			if ( ( new WPMUDEV() )->show_support_links() ) {
				$string .= defender_support_ticket_text();
			}

			return new Response( false, array( 'invalid_text' => $string ) );
		}
	}

	/**
	 * Delete the Maxmind License key from the settings.
	 *
	 * @return Response
	 * @defender_route
	 * @throws InvalidDatabaseException When unexpected data is found in the database.
	 */
	public function delete_geodb(): Response {
		$this->model->maxmind_license_key = '';
		$this->model->geodb_path          = '';
		$this->model->save();

		return new Response(
			true,
			array(
				'message'          => esc_html__(
					'Maxmind GeoLite2 database license successfully disconnected.',
					'wpdef'
				),
				'is_geodb_deleted' => $this->service->is_geodb_downloaded(),
			)
		);
	}

	/**
	 * Export IPs
	 * This method exports the IP addresses from the blocklist and allowlist
	 * and generates a CSV file for download.
	 *
	 * @defender_route
	 */
	public function export_ips(): void {
		$data = array();

		foreach ( $this->model->get_list( 'blocklist' ) as $ip ) {
			$data[] = array(
				'ip'   => $ip,
				'type' => 'blocklist',
			);
		}
		foreach ( $this->model->get_list( 'allowlist' ) as $ip ) {
			$data[] = array(
				'ip'   => $ip,
				'type' => 'allowlist',
			);
		}
		// WP_Filesystem class doesn’t directly provide a function for opening a stream to php://memory with the 'w' mode.
		$fp = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		foreach ( $data as $fields ) {
			fputcsv( $fp, $fields, ',', '"', '\\' );
		}
		$filename = 'wdf-ips-export-' . wp_date( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// Make php send the generated csv lines to the browser.
		fpassthru( $fp );
		exit();
	}

	/**
	 * Perform IP blocking or unblocking actions.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return void
	 * @throws Exception If table is not defined.
	 * @defender_route
	 */
	public function ip_action( Request $request ): void {
		$data = $request->get_data(
			array(
				'ip'       => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'behavior' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$ip     = $data['ip'];
		$action = $data['behavior'];
		$models = Lockout_Ip::get( $ip, $action, true );

		foreach ( $models as $model ) {
			if ( 'unban' === $action ) {
				$model->status = Lockout_Ip::STATUS_NORMAL;
				$model->save();
			} elseif ( 'ban' === $action ) {
				$model->status = Lockout_Ip::STATUS_BLOCKED;
				$model->save();
			}
		}

		$this->query_locked_ips( $request );
	}

	/**
	 * Bulk ban or unban IPs.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @throws Exception If table is not defined.
	 * @defender_route
	 */
	public function bulk_ip_action( Request $request ) {
		$data = $request->get_data(
			array(
				'behavior' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ips'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$status   = 'unban' === $data['behavior'] ? Lockout_Ip::STATUS_BLOCKED : Lockout_Ip::STATUS_NORMAL;
		$ips      = null;
		$bulk_ips = null;
		$limit    = 50;

		if ( ! empty( $data['ips'] ) ) {
			$ips           = json_decode( $data['ips'] );
			$first_nth_ips = array_slice( $ips, 0, $limit );
			$bulk_ips      = wp_list_pluck( $first_nth_ips, 'ip' );
		}

		try {
			$models = Lockout_Ip::get_bulk( $status, $bulk_ips, $limit );
			foreach ( $models as $model ) {
				$model->status = ( 'unban' === $data['behavior'] ) ? Lockout_Ip::STATUS_NORMAL : Lockout_Ip::STATUS_BLOCKED;
				$model->save();
			}
			// While bulk banning the IPs, needs to slice the IPs array for next iteration.
			if ( 'ban' === $data['behavior'] ) {
				$ips = array_slice( $ips, $limit );
			}
			// If the queried models are less than the limit it means we are on the last set of IPs.
			if ( ( is_array( $models ) || $models instanceof Countable ? count( $models ) : 0 ) < $limit ) {
				return new Response(
					true,
					array(
						'status' => 'done',
					)
				);
			}
		} catch ( Exception $e ) {
			return new Response(
				true,
				array(
					'status' => 'error',
				)
			);
		}

		return new Response(
			true,
			array(
				'status' => 'continue',
				'ips'    => $ips,
			)
		);
	}

	/**
	 * Query locked IPs and return the results as a Response object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function query_locked_ips() {
		$results    = Lockout_Ip::query_locked_ip();
		$locked_ips = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $locked_ip ) {
				$locked_ips[] = array(
					'id'     => $locked_ip['id'],
					'ip'     => $locked_ip['ip'],
					'status' => $locked_ip['status'],
				);
			}
		}

		return new Response(
			true,
			array(
				'ips' => $locked_ips,
			)
		);
	}

	/**
	 * Get Listed IPs.
	 *
	 * @return Response
	 * @defender_route
	 * @throws Exception If table is not defined.
	 */
	public function get_listed_ips(): Response {
		return new Response( true, $this->model->export() );
	}

	/**
	 * Converts the current state of the object to an array.
	 *
	 * @return array Returns an associative array of object properties.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Adapt the given data array by adding additional fields if necessary.
	 *
	 * @param  array $data  The data array to adapt.
	 *
	 * @return array The adapted data array.
	 */
	private function adapt_data( array $data ): array {
		$adapted_data = array(
			'ip_blacklist'       => $data['ip_blacklist'],
			'ip_whitelist'       => $data['ip_whitelist'],
			'ip_lockout_message' => $data['ip_lockout_message'],
		);
		if ( isset( $data['geoIP_db'] ) && file_exists( $data['geoIP_db'] ) ) {
			$adapted_data['geodb_path'] = $data['geoIP_db'];
			if ( isset( $data['country_blacklist'] ) ) {
				$adapted_data['country_blacklist'] = $data['country_blacklist'];
			}
			if ( isset( $data['country_whitelist'] ) ) {
				$adapted_data['country_whitelist'] = $data['country_whitelist'];
			}
		}

		return array_merge( $data, $adapted_data );
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 *
	 * @throws Exception If table is not defined.
	 */
	public function import_data( array $data ) {
		if ( ! empty( $data ) ) {
			// Upgrade for old versions.
			$data  = $this->adapt_data( $data );
			$model = $this->model;
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
		}
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
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Importing IPs from exporter.
	 *
	 * @param  Request $request  The request object containing the data.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function import_ips( Request $request ) {
		$data        = $request->get_data(
			array(
				'id' => array(
					'type' => 'int',
				),
			)
		);
		$attached_id = $data['id'];
		if ( ! is_object( get_post( $attached_id ) ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$file = get_attached_file( $attached_id );
		if ( ! is_file( $file ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$data = $this->service->verify_import_file( $file );
		if ( ! $data ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Your file content is invalid!', 'wpdef' ),
				)
			);
		}

		// All good, start to import.
		foreach ( $data as $line ) {
			$this->model->add_to_list( $line[0], $line[1] );
		}

		return new Response(
			true,
			array(
				'message'  => esc_html__( 'Your allowlist/blocklist has been successfully imported.', 'wpdef' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * Update the geolocation database.
	 *
	 * @return void
	 * @throws Exception If table is not defined.
	 * @since 2.8.0
	 */
	public function update_database() {
		if ( empty( $this->model->maxmind_license_key ) ) {
			return;
		}

		$service_geo = wd_di()->get( MaxMind_Geolocation::class );
		$service_geo->delete_database();

		$tmp = $service_geo->get_downloaded_url( $this->model->maxmind_license_key );
		if ( is_wp_error( $tmp ) ) {
			$this->log( 'CRON error downloading from MaxMind: ' . $tmp->get_error_message(), Firewall::FIREWALL_LOG );

			return;
		}

		$geodb_path = $service_geo->extract_db_archive( $tmp );
		if ( is_wp_error( $geodb_path ) ) {
			$this->log( 'CRON error extracting MaxMind archive: ' . $geodb_path->get_error_message(), Firewall::FIREWALL_LOG );

			return;
		}
		$this->model->geodb_path = $geodb_path;
		$this->model->save();
	}
}