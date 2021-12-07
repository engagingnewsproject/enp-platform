<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Traits\Country;
use WP_Defender\Traits\IP;

/**
 * Class Blacklist
 *
 * @package WP_Defender\Controller
 */
class Blacklist extends Controller2 {
	use IP, Country;

	/**
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * @var \WP_Defender\Model\Setting\Blacklist_Lockout
	 */
	protected $model;

	/**
	 * @var \WP_Defender\Component\Blacklist_Lockout
	 */
	protected $service;

	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Blacklist_Lockout::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Blacklist_Lockout::class );
		add_action( 'wd_blacklist_this_ip', array( &$this, 'blacklist_an_ip' ) );
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
		$user_ip      = $this->get_user_ip();
		$country_list = $this->countries_list();
		$arr_model    = $this->model->export();

		return array_merge(
			array(
				'model' => $arr_model,
				'misc'  => array(
					'user_ip'             => $user_ip,
					'is_geodb_downloaded' => $this->model->is_geodb_downloaded(),
					'blacklist_countries' => array_merge( array( 'all' => __( 'Block all', 'wpdef' ) ), $country_list ),
					'whitelist_countries' => array_merge( array( 'all' => __( 'Allow all', 'wpdef' ) ), $country_list ),
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
		$url         = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=$license_key&suffix=tar.gz";
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$tmp         = download_url( $url );
		if ( ! is_wp_error( $tmp ) ) {
			$phar = new \PharData( $tmp );
			$path = $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'maxmind';
			if ( ! is_dir( $path ) ) {
				wp_mkdir_p( $path );
			}
			$phar->extractTo( $path, null, true );
			$this->model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';
			$country                 = $this->get_current_country( $this->get_user_ip() );
			if ( empty( $this->model->country_whitelist ) ) {
				$this->model->country_whitelist[] = $country['iso'];
			}
			$this->model->save();

			return new Response( true, [
				'message'             => __(
					'You have successfully downloaded Geo IP Database. You can now use this feature to ban any countries to access any area of your website.',
					'wpdef'
				),
				'is_geodb_downloaded' => $this->model->is_geodb_downloaded(),
				'current_country'     => $country['iso'],
				'min_php_version'     => WP_DEFENDER_MIN_PHP_VERSION,
			] );
		} else {
			$this->log( 'Error from MaxMind: ' . $tmp->get_error_message() );
			$string = sprintf(
			/* translators: ... */
				__(
					'The license key you entered is not valid. You can find your license key on the <a target="_blank" href="%s">Services / My License Key page</a>.',
					'wpdef'
				),
				'https://www.maxmind.com/en/accounts/current/license-key'
			);

			if ( ( new \WP_Defender\Behavior\WPMUDEV() )->show_support_links() ) {
				$string .= sprintf(
				/* translators: ... */
					__(
						' If you continue having connection issues, our <a target="_blank" href="%s">support team</a> is ready to help.',
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
}
