<?php

namespace WP_Defender\Component\Config;

use WP_Defender\Component\Backup_Settings;
use WP_Defender\Behavior\WPMUDEV;

/**
 * Hub_Helper class
 */
class Config_Hub_Helper {

	const CONFIGS_TRANSIENT_KEY = 'wpdefender_preset_configs';
	const CONFIGS_TRANSIENT_TIME_KEY = 'wpdefender_preset_configs_transient_time';
	const ACTIVE_FLAG_CLEAR_KEY = 'wpdefender_config_clear_active_tag';
	const CONFIGS_TRANSIENT_TIME = 600; // 600 = 10 minutes.

	/**
	 * Service class.
	 *
	 * @var Backup_Settings
	 */
	private $service;

	/**
	 * Get configs from transient.
	 *
	 * @param Backup_Settings $service Config service.
	 *
	 * @return array
	 */
	public static function get_configs( Backup_Settings $service ) {
		$configs = get_site_transient( self::CONFIGS_TRANSIENT_KEY );

		if ( false === $configs ) {
			$_this          = new self();
			$_this->service = $service;
			$configs        = $_this->fetch_current_configs();

			set_site_transient( self::CONFIGS_TRANSIENT_KEY, $configs, self::CONFIGS_TRANSIENT_TIME );
			update_site_option( self::CONFIGS_TRANSIENT_TIME_KEY, time() );
		}

		return $configs;
	}

	/**
	 * Fetch and prepare configs to store to transient.
	 *
	 * @return array
	 */
	private function fetch_current_configs() {
		$stored_configs = $this->service->get_configs();
		$wpmudev        = new WPMUDEV();
		$def_details    = $wpmudev->get_plugin_details();

		// Check config has `hub_id`, If not then send requst to HUB and set it.
		$stored_configs = $this->check_and_save_configs_to_hub( $stored_configs, $def_details, $wpmudev );

		if ( defined( WPMUDEV::class . '::API_PACKAGE_CONFIGS' ) ) {
			// Fetch configs from API.
			$response = $wpmudev->make_wpmu_request(
				WPMUDEV::API_PACKAGE_CONFIGS,
				array( 'package_id' => $def_details['id'] ),
				array( 'method' => 'GET' )
			);
		} else {
			$response = false;
		}

		$final_configs = array();

		// Loop to set default config.
		// Exclude default config from match with HUB config.
		foreach ( $stored_configs as $sc_key => $sc_value ) {
			if ( 0 === strpos( $sc_key, 'wp_defender_config_default' ) ) {
				$final_configs[ $sc_key ] = $sc_value;
				break;
			}

			// Sometimes key does not match for default config.
			if ( isset( $sc_value['immortal'] ) && $sc_value['immortal'] ) {
				$final_configs[ $sc_key ] = $sc_value;
				break;
			}
		}

		return $this->prepare_hub_configs_response( $response, $final_configs, $stored_configs );
	}

	/**
	 * Check configs are saved on hub. If not in hub then create it.
	 *
	 * @param array   $stored_configs All configs from DB.
	 * @param array   $def_details    Defender plugin details.
	 * @param WPMUDEV $wpmudev        Defender plugin details.
	 *
	 * @return array
	 */
	private function check_and_save_configs_to_hub( $stored_configs, $def_details, $wpmudev ) {
		foreach ( $stored_configs as $sc_key => &$sc_value ) {
			// Check it is for default config.
			if ( 0 === strpos( $sc_key, 'wp_defender_config_default' ) ) {
				continue;
			}

			// Sometimes key does not match for default config.
			if ( isset( $sc_value['immortal'] ) && $sc_value['immortal'] ) {
				continue;
			}

			if ( isset( $sc_value['hub_id'] ) ) {
				continue;
			}

			$hub_id = $this->insert_to_hub( $sc_value, $def_details, $wpmudev );

			// If data is not inserted to HUB.
			if ( ! $hub_id ) {
				continue;
			}

			$sc_value['hub_id'] = $hub_id;
		}

		return $stored_configs;
	}

	/**
	 * Insert new config to hub.
	 *
	 * @param array   $sc_value    Single config from DB.
	 * @param array   $def_details Defender plugin details.
	 * @param WPMUDEV $wpmudev     Defender plugin details.
	 *
	 * @return int ID from hub.
	 */
	private function insert_to_hub( $sc_value, $def_details, $wpmudev ) {
		if ( ! defined( WPMUDEV::class . '::API_PACKAGE_CONFIGS' ) ) {
			return false;
		}

		if ( empty( $sc_value['configs'] ) ) {
			return false;
		}

		if ( empty( $sc_value['labels'] ) ) {
			$config_component   = wd_di()->get( Backup_Settings::class );
			$sc_value['labels'] = $config_component->prepare_config_labels( $sc_value['configs'] );
		}

		$data = array(
			'name'        => $sc_value['name'],
			'description' => $sc_value['description'],
			'package'     => $def_details,
			'config'      => wp_json_encode(
				array(
					'strings' => $sc_value['strings'],
					'configs' => $sc_value['configs'],
					'labels'  => $sc_value['labels'],
				)
			),
		);

		$response = $wpmudev->make_wpmu_request(
			WPMUDEV::API_PACKAGE_CONFIGS,
			$data,
			array( 'method' => 'POST' )
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return $response['id'];
	}

	/**
	 * Add config to HUB.
	 *
	 * @param array $data Config data.
	 *
	 * @return int
	 */
	public static function add_configs_to_hub( $data ) {
		$_this       = new self();
		$wpmudev     = new WPMUDEV();
		$def_details = $wpmudev->get_plugin_details();

		delete_site_transient( self::CONFIGS_TRANSIENT_KEY );

		return $_this->insert_to_hub( $data, $def_details, $wpmudev );
	}

	/**
	 * Delete config from HUB.
	 *
	 * @param int $hub_id ID of config from HUB.
	 *
	 * @return bool
	 */
	public static function delete_configs_from_hub( $hub_id ) {
		if ( ! defined( WPMUDEV::class . '::API_PACKAGE_CONFIGS' ) ) {
			return false;
		}

		$wpmudev  = new WPMUDEV();
		$url      = $wpmudev->get_endpoint( WPMUDEV::API_PACKAGE_CONFIGS );
		$response = self::send_request(
			$url . '/' . $hub_id,
			array(),
			$wpmudev->get_apikey(),
			'DELETE'
		);

		if ( isset( $response['deleted'] ) && $response['deleted'] ) {
			delete_site_transient( self::CONFIGS_TRANSIENT_KEY );

			return true;
		}

		return false;
	}

	/**
	 * Update config on HUB.
	 *
	 * @param array $config Full config data.
	 *
	 * @return bool
	 */
	public static function update_on_hub( $config ) {
		if ( ! isset( $config['hub_id'] ) ) {
			return false;
		}

		if ( ! defined( WPMUDEV::class . '::API_PACKAGE_CONFIGS' ) ) {
			return false;
		}

		$wpmudev = new WPMUDEV();
		$url     = $wpmudev->get_endpoint( WPMUDEV::API_PACKAGE_CONFIGS );

		$data = array(
			'name'        => $config['name'],
			'description' => $config['description'],
		);

		$response = self::send_request(
			$url . '/' . $config['hub_id'],
			$data,
			$wpmudev->get_apikey(),
			'PUT'
		);

		if ( isset( $response['id'] ) && $response['id'] ) {
			delete_site_transient( self::CONFIGS_TRANSIENT_KEY );

			return true;
		}

		return false;
	}

	/**
	 * API request.
	 *
	 * @return bool|array
	 */
	private static function send_request( $url, $body, $api_key, $method ) {
		$request = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'headers' => array(
					'Authorization' => $api_key,
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $request ) ) {
			return false;
		}

		return json_decode( $request['body'], true );
	}

	/**
	 * Delete transient for matching condition.
	 *
	 * @return void
	 */
	public static function clear_config_transient() {
		if ( ! isset( $_GET['page'], $_GET['view'], $_GET['transient'] ) ) {
			return;
		}

		// If page is not config.
		if ( ! ( 'wdf-setting' === $_GET['page'] && 'configs' === $_GET['view'] ) ) {
			return;
		}

		if ( 'clear' === $_GET['transient'] ) {
			delete_site_transient( self::CONFIGS_TRANSIENT_KEY );
		}
	}

	/**
	 * Prepare configs getting from HUB.
	 */
	private function prepare_hub_configs_response( $response, $final_configs, $stored_configs ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return $stored_configs;
		}

		// Store id of keys that need to delete. Because they are deleted on HUB.
		$delete_hub_ids    = array();
		$once_delete_unset = array();

		// Loop through all items found in the API.
		foreach ( $response as $api_config ) {
			$found = false;

			// Find key and value from stored configs.
			foreach ( $stored_configs as $sc_key => $sc_value ) {
				if ( isset( $sc_value['hub_id'] ) ) {
					// Once it is unset from the deleted array don't re-add it.
					if ( ! isset( $once_delete_unset[ $sc_value['hub_id'] ] ) ) {
						$delete_hub_ids[ $sc_value['hub_id'] ] = $sc_key;
					}

					if ( $sc_value['hub_id'] === $api_config['id'] ) {
						$sc_value['name']         = $api_config['name'];
						$sc_value['description']  = $api_config['description'];
						$final_configs[ $sc_key ] = $sc_value;
						$found                    = true;

						unset( $delete_hub_ids[ $sc_value['hub_id'] ] );
						$once_delete_unset[ $sc_value['hub_id'] ] = $sc_key;
						break;
					}
				}
			}

			if ( $found ) {
				continue;
			}

			// Need to prepare this data because these are coming from HUB.
			$key = 'wp_defender_config_hub_' . $api_config['id'];

			$final_configs[ $key ] = $this->format_hub_config_to_save( $api_config );
			$this->service->index_key( $key );
		}

		$this->delete_hub_removed_configs( $delete_hub_ids );

		return $final_configs;
	}

	/**
	 * Delete configs that are delete from HUB.
	 *
	 * @param array $delete_hub_ids
	 */
	private function delete_hub_removed_configs( $delete_hub_ids ) {
		foreach ( $delete_hub_ids as $key ) {
			delete_site_option( $key );
			$this->service->remove_index( $key );
		}
	}

	/**
	 * Need to prepare this data because these are coming from HUB.
	 *
	 * @param $api_config
	 *
	 * @return array
	 */
	private function format_hub_config_to_save( $api_config ) {
		$api_config_array = json_decode( $api_config['config'], true );

		$api_config_array['hub_id']      = $api_config['id'];
		$api_config_array['name']        = $api_config['name'];
		$api_config_array['description'] = $api_config['description'];
		$api_config_array['immortal']    = false;

		unset( $api_config_array['labels'] );

		return $api_config_array;
	}

	/**
	 * Delete config cache and create a new cache.
	 *
	 * @param Backup_Settings $service
	 *
	 * @return array
	 */
	public static function get_fresh_frontend_configs( Backup_Settings $service ) {
		delete_site_transient( self::CONFIGS_TRANSIENT_KEY );

		$configs = self::get_configs( $service );

		foreach ( $configs as &$config ) {
			unset( $config['configs'] );
		}

		return $configs;
	}

	/**
	 * Set a flag to clear active tag.
	 */
	public static function set_clear_active_flag() {
		$value = get_site_option( self::ACTIVE_FLAG_CLEAR_KEY, 'cleared' );

		if ( 'clear' !== $value ) {
			update_site_option( self::ACTIVE_FLAG_CLEAR_KEY, 'clear' );
		}
	}

	/**
	 * Check active flag need to remove or not.
	 *
	 * @return bool
	 */
	public static function check_remove_active_flag() {
		$value = get_site_option( self::ACTIVE_FLAG_CLEAR_KEY, 'cleared' );

		if ( 'clear' === $value ) {
			update_site_option( self::ACTIVE_FLAG_CLEAR_KEY, 'cleared' );

			return true;
		}

		return false;
	}

	/**
	 * Active a config from.
	 *
	 * @param $hub_id
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public static function active_config_from_hub_id( $hub_id ) {
		$config_component = wd_di()->get( Backup_Settings::class );
		$configs          = $config_component->get_configs();

		foreach ( $configs as $key => $config ) {
			$need_update = false;

			// Remove previous active status.
			if ( $config['is_active'] ) {
				$config['is_active'] = false;
				$need_update         = true;
			}

			// Add current active status.
			if ( (int) $config['hub_id'] === (int) $hub_id ) {
				$config['is_active'] = true;
				$need_update         = true;
			}

			if ( $need_update ) {
				update_site_option( $key, $config );
			}
		}

		// Clear cache to reflect in frontend.
		delete_site_transient( self::CONFIGS_TRANSIENT_KEY );
	}
}
