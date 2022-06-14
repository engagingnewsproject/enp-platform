<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;
use WP_Defender\Traits\Country;
use WP_Defender\Traits\IP;
use WP_Defender\Integrations\MaxMind_Geolocation;

/**
 * Class Blacklist
 *
 * @package WP_Defender\Controller
 */
class Blacklist extends Controller {
	use IP, Country;

	/**
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * @var Model_Blacklist_Lockout
	 */
	protected $model;

	/**
	 * @var \WP_Defender\Component\Blacklist_Lockout
	 */
	protected $service;

	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Model_Blacklist_Lockout::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Blacklist_Lockout::class );
		add_action( 'wd_blacklist_this_ip', array( &$this, 'blacklist_an_ip' ) );
		// Update MaxMind's DB.
		if ( ! empty( $this->model->maxmind_license_key ) ) {
			if ( ! wp_next_scheduled( 'wpdef_update_geoip' ) ) {
				wp_schedule_event( strtotime( 'next Thursday' ), 'weekly', 'wpdef_update_geoip' );
			}
			// @since 2.8.0 Allows update or remove the database of MaxMind automatic and periodically (MaxMind's TOS).
			$bind_updater = (bool) apply_filters( 'wd_update_maxmind_database', true );
			// Bind to the scheduled updater action.
			if ( $bind_updater ) {
				add_action( 'wpdef_update_geoip', array( &$this, 'update_database' ) );
			}
		}
	}

	/**
	 * Add an IP into blacklist.
	 *
	 * @param $ip
	 */
	public function blacklist_an_ip( $ip ) {
		$this->model->add_to_list( $ip, 'blocklist' );
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'blacklist', $this->data_frontend() );
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		$user_ip     = $this->get_user_ip();
		$arr_model   = $this->model->export();
		$exist_geodb = $this->service->is_geodb_downloaded();
		// If MaxMind GeoIP DB is downloaded then display the required data.
		if ( $exist_geodb ) {
			$country_list = $this->countries_list();
			$blacklist_countries = array_merge( array( 'all' => __( 'Block all', 'wpdef' ) ), $country_list );
			$whitelist_countries = array_merge( array( 'all' => __( 'Allow all', 'wpdef' ) ), $country_list );
		} else {
			$blacklist_countries = $whitelist_countries = array();
		}

		return array_merge(
			array(
				'model' => $arr_model,
				'misc'  => array(
					'user_ip'             => $user_ip,
					'is_geodb_downloaded' => $exist_geodb,
					'blacklist_countries' => $blacklist_countries,
					'whitelist_countries' => $whitelist_countries,
					'current_country'     => $this->get_current_country( $user_ip ),
					'geo_requirement'     => version_compare( PHP_VERSION, WP_DEFENDER_MIN_PHP_VERSION, '>=' ),
					'min_php_version'     => WP_DEFENDER_MIN_PHP_VERSION,
					'no_ips'              => '' === $arr_model['ip_blacklist'] && '' === $arr_model['ip_whitelist'],
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data(
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
						'message' => __( 'Your settings have been updated.', 'wpdef' ),
					),
					$this->data_frontend()
				)
			);
		}

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
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function download_geodb( Request $request ) {
		$data        = $request->get_data( [
			'license_key' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			]
		] );
		$license_key = $data['license_key'];
		$service_geo = wd_di()->get( MaxMind_Geolocation::class );
		$tmp         = $service_geo->get_downloaded_url( $license_key );
		if ( ! is_wp_error( $tmp ) ) {
			$phar = new \PharData( $tmp );
			$path = $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'maxmind';
			if ( ! is_dir( $path ) ) {
				wp_mkdir_p( $path );
			}
			$phar->extractTo( $path, null, true );
			// Todo: move logic for the path to MaxMind_Geolocation class.
			$this->model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $service_geo->get_db_full_name();
			// Save because we'll check for a saved path.
			$this->model->save();

			if ( file_exists( $tmp ) ) {
				unlink( $tmp );
			}

			$country                 = $this->get_current_country( $this->get_user_ip() );
			$current_country         = '';
			if ( ! empty( $country ) && ! empty( $country['iso'] ) ) {
				$current_country = $country['iso'];
				$this->model     = $this->service->add_default_whitelisted_country( $this->model, $country['iso'] );
			}
			$this->model->maxmind_license_key = $license_key;
			$this->model->save();

			return new Response( true, [
				'message'             => __(
					'You have successfully downloaded Geo IP Database. You can now use this feature to ban any countries to access any area of your website.',
					'wpdef'
				),
				'is_geodb_downloaded' => $this->service->is_geodb_downloaded(),
				'current_country'     => $current_country,
				'min_php_version'     => WP_DEFENDER_MIN_PHP_VERSION,
			] );
		} else {
			$this->log( 'Error from MaxMind: ' . $tmp->get_error_message() );
			$string = sprintf(
			/* translators: ... */
				__(
					'You have entered an invalid <a target="_blank" href="%s">license key</a>. If you just created the key, please wait 5 minutes before trying to activate it.',
					'wpdef'
				),
				'https://www.maxmind.com/en/accounts/current/license-key'
			);

			if ( ( new \WP_Defender\Behavior\WPMUDEV() )->show_support_links() ) {
				$string .= sprintf(
				/* translators: ... */
					__(
						' Still having trouble? <a target="_blank" href="%s">Open a support ticket</a>.',
						'wpdef'
					),
					'https://wpmudev.com/hub2/support/#get-support'
				);
			}
			return new Response( false, array( 'invalid_text' => $string ) );
		}
	}

	/**
	 * @defender_route
	 */
	public function export_ips() {
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

		$fp = fopen( 'php://memory', 'w' );
		foreach ( $data as $fields ) {
			fputcsv( $fp, $fields );
		}
		$filename = 'wdf-ips-export-' . gmdate( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// Make php send the generated csv lines to the browser.
		fpassthru( $fp );
		exit();
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function ip_action( Request $request ) {
		$data   = $request->get_data( [
			'ip'       => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
			'behavior' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
		] );

		$ip     = $data['ip'];
		$action = $data['behavior'];
		$models  = Lockout_Ip::get( $ip, $action, true );

		foreach( $models as $model )  {
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
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function bulk_ip_action( Request $request ) {
		$data   = $request->get_data( [
			'behavior' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
			'ips' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
		] );

		$status   = 'unban' === $data['behavior'] ? Lockout_Ip::STATUS_BLOCKED : Lockout_Ip::STATUS_NORMAL;
		$ips      = null;
		$bulk_ips = null;
		$limit	  = 50;

		if ( ! empty( $data['ips'] ) ) {
			$ips           = json_decode( $data['ips'] );
			$first_nth_ips = array_slice( $ips, 0, $limit );
			$bulk_ips      = wp_list_pluck( $first_nth_ips, 'ip' );
		}

		try {
			$models  = Lockout_Ip::get_bulk( $status, $bulk_ips, $limit );
			foreach( $models as $model )  {
				$model->status = ( 'unban' === $data['behavior'] ) ? Lockout_Ip::STATUS_NORMAL : Lockout_Ip::STATUS_BLOCKED;
				$model->save();
			}
			// While bulk banning the IPs, needs to slice the IPs array for next iteration.
			if ( 'ban' === $data['behavior'] ) {
				$ips = array_slice( $ips, $limit );
			}
			// If the queried models are less than the limit it means we are on the last set of IPs.
			if ( count( $models ) < $limit ) {
				return new Response( true, [
					'status' => 'done'
				] );
			}
		} catch( \Exception $e ) {
			return new Response( true, [
				'status' => 'error'
			] );
		}

		return new Response( true, [
			'status' => 'continue',
			'ips' 	 => $ips
		] );
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function query_locked_ips( Request $request ) {
		$results    = \WP_Defender\Model\Lockout_Ip::query_locked_ip();
		$locked_ips = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $locked_ip ) {
				$locked_ips[] = array(
					'id'     => $locked_ip->id,
					'ip'     => $locked_ip->ip,
					'status' => $locked_ip->status,
				);
			}
		}

		return new Response( true, [
			'ips' => $locked_ips
		] );
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 */
	public function to_array() {}

	/**
	 * @param array $data
	 *
	 * @return array
	*/
	private function adapt_data( $data ) {
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
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 * @param array $data
	 *
	 * @return void
	 */
	public function import_data( $data ) {
		if ( ! empty( $data ) ) {
			// Upgrade for old versions.
			$data = $this->adapt_data( $data );
		} else {
			return;
		}

		$model = $this->model;
		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

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
		return [];
	}

	/**
	 * Importing IPs from exporter.
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function import_ips( Request $request ) {
		$data = $request->get_data(
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
					'message' => __( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$file = get_attached_file( $attached_id );
		if ( ! is_file( $file ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$data = $this->service->verify_import_file( $file );
		if ( ! $data ) {
			return new Response(
				false,
				array(
					'message' => __( 'Your file content is invalid!', 'wpdef' ),
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
				'message' => __( 'Your allowlist/blocklist has been successfully imported.', 'wpdef' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * Update the geolocation database.
	 *
	 * @since 2.8.0
	 *
	 * @return void
	 */
	public function update_database() {
		if ( empty( $this->model->maxmind_license_key ) ) {
			return;
		}

		$service_geo = wd_di()->get( MaxMind_Geolocation::class );
		$service_geo->delete_database();

		$tmp = $service_geo->get_downloaded_url( $this->model->maxmind_license_key );
		if ( is_wp_error( $tmp ) ) {
			$this->log( 'CRON error downloading from MaxMind: ' . $tmp->get_error_message() );
			return;
		}

		$geodb_path = $service_geo->extract_db_archive( $tmp );
		if ( is_wp_error( $geodb_path ) ) {
			$this->log( 'CRON error extracting MaxMind archive: ' . $geodb_path->get_error_message() );
			return;
		}
		$this->model->geodb_path = $geodb_path;
		$this->model->save();
	}
}
