<?php
/**
 * Configs module class.
 *
 * @since 3.0.1
 * @package Hummingbird\Core
 */

namespace Hummingbird\Core;

use Exception;
use Hummingbird\Core\Integration\Opcache;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Configs
 */
class Configs {

	/**
	 * Basic config defaults (that are different from plugin defaults).
	 *
	 * @var array $defaults
	 */
	private $defaults = array(
		'gravatar'   => array(
			'enabled' => true,
		),
		'page_cache' => array(
			'enabled' => true,
		),
		'settings'   => array(
			'control' => true,
		),
	);

	/**
	 * Enqueue scripts and styles for React.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_react_scripts() {
		wp_enqueue_script( 'wphb-react-configs', WPHB_DIR_URL . 'admin/assets/js/wphb-react-configs.min.js', array( 'wp-i18n', 'lodash' ), WPHB_VERSION, true );

		$api = Utils::get_api();
		wp_localize_script(
			'wphb-react-configs',
			'wphbReact',
			array(
				'links'        => array(
					'accordionImg' => WPHB_DIR_URL . 'admin/assets/image/icon-configs@2x.png',
					'hubConfigs'   => Utils::get_link( 'configs' ),
					'hubWelcome'   => Utils::get_link( 'hub-welcome', 'config' ),
					'configsPage'  => Utils::get_admin_menu_url( 'settings' ) . '&view=configs',
				),
				'module'       => array(
					'isMember'       => Utils::is_member(),
					'isWhiteLabeled' => apply_filters( 'wpmudev_branding_hide_branding', false ),
				),
				'requestsData' => array(
					'root'           => esc_url_raw( rest_url( $api->rest->namespace . '/v' . $api->rest->version . '/preset_configs' ) ), // Or make get_namespace() public.
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'apiKey'         => Utils::get_api()->minify->get_request()->get_api_key(),
					'hubBaseURL'     => defined( 'WPHB_TEST_API_URL' ) && WPHB_TEST_API_URL ? WPHB_TEST_API_URL . 'hub/v1/package-configs' : null,
					'pluginData'     => array(
						'name' => Utils::is_member() ? 'Hummingbird Pro' : 'Hummingbird',
						'id'   => '1081721',
					),
					'pluginRequests' => array(
						'nonce'        => wp_create_nonce( 'wphb-fetch' ),
						'uploadAction' => 'wphb_upload_config',
						'createAction' => 'wphb_create_config',
						'applyAction'  => 'wphb_apply_config',
					),
				),
			)
		);

		wp_add_inline_script(
			'wphb-react-configs',
			'wp.i18n.setLocaleData( ' . wp_json_encode( Utils::get_locale_data() ) . ', "wphb" );',
			'before'
		);
	}

	/**
	 * Adds the basic configuration to the local configs.
	 *
	 * @since 3.0.1
	 */
	public function get_basic_config() {
		$settings = Settings::get_default_settings();
		ksort( $settings );

		$settings = $this->remove_non_unique_settings( $settings );
		$settings = array_replace_recursive( $settings, $this->defaults );
		$settings = $this->remove_disabled_options( $settings );

		$basic_config = array(
			'id'          => 1,
			'name'        => __( 'Basic config', 'wphb' ),
			'description' => __( 'Recommended performance config for every site.', 'wphb' ),
			'default'     => true,
			'config'      => array(
				'configs' => array(
					'settings' => $settings,
				),
			),
		);

		$basic_config['config']['strings'] = $this->format_config_to_display( $basic_config['config']['configs'] );

		return $basic_config;
	}

	/**
	 * Gets a new config array based on the current settings.
	 *
	 * @since 3.0.1
	 *
	 * @return array
	 */
	public function get_config_from_current() {
		$settings = Settings::get_settings();
		ksort( $settings );

		$settings = $this->remove_non_unique_settings( $settings );
		$settings = $this->remove_disabled_options( $settings );

		$configs = compact( 'settings' );

		return array(
			'config' => array(
				'configs' => $configs,
				'strings' => $this->format_config_to_display( $configs ),
			),
		);
	}

	/**
	 * Save uploaded config.
	 *
	 * @since 3.0.1
	 *
	 * @param array $file  Uploaded file.
	 */
	public function save_uploaded_config( $file ) {
		try {
			$config = $this->decode_and_validate_config_file( $file );
		} catch ( Exception $e ) {
			return new WP_Error( 'error_saving', $e->getMessage() );
		}

		return $config;
	}

	/**
	 * Apply a config based on a given ID.
	 *
	 * @since 3.0.1
	 *
	 * @param int $id The ID of the config to apply.
	 */
	public function apply_config_by_id( $id ) {
		$stored_configs = get_site_option( 'wphb-preset_configs', array() );

		$config = false;
		foreach ( $stored_configs as $config_data ) {
			if ( $config_data['id'] === $id ) {
				$config = $config_data;
				break;
			}
		}

		// The config with the given ID doesn't exist.
		if ( ! $config ) {
			return new WP_Error( '404', __( 'The given config ID does not exist', 'wphb' ) );
		}

		$this->apply_config( $config['config']['configs'] );
	}

	/**
	 * Apply selected config.
	 *
	 * @since 3.0.1
	 *
	 * @param array $config  Config to apply.
	 */
	public function apply_config( $config ) {
		$settings = Settings::get_settings();

		$new_settings = array_replace_recursive( $settings, $config['settings'] );

		// Disable opcache integration if opcache not enabled on server.
		$opcache_option = isset( $new_settings['page_cache'] ) && isset( $new_settings['page_cache']['integrations'] ) && isset( $new_settings['page_cache']['integrations']['opcache'] );
		if ( true === $opcache_option && ! Opcache::get_instance()->is_enabled() ) {
			$new_settings['page_cache']['integrations']['opcache'] = false;
		}

		// Disable WooCommerce cart fragments if site does not have WooCommerce.
		$cart_fragments = isset( $new_settings['advanced'] ) && isset( $new_settings['advanced']['cart_fragments'] );
		if ( true === $cart_fragments && ! class_exists( 'woocommerce' ) ) {
			$new_settings['advanced']['cart_fragments'] = false;
		}

		Settings::update_settings( $new_settings );

		// Enable Uptime module.
		if ( $config['settings']['uptime']['enabled'] && $config['settings']['uptime']['enabled'] !== $settings['uptime']['enabled'] ) {
			Utils::get_module( 'uptime' )->enable();
			Utils::get_module( 'uptime' )->get_last_report( 'week', true );
		}
	}

	/**
	 * Decode and validate the uploaded config file.
	 *
	 * @since 3.0.1
	 *
	 * @param array $file  Uploaded file.
	 *
	 * @return array
	 * @throws Exception  When there's an error with the uploaded file.
	 */
	private function decode_and_validate_config_file( $file ) {
		if ( ! $file ) {
			throw new Exception( __( 'The configs file is required', 'wphb' ) );
		} elseif ( ! empty( $file['error'] ) ) {
			/* translators: error message */
			throw new Exception( sprintf( __( 'Error: %s.', 'wphb' ), $file['error'] ) );
		} elseif ( 'application/json' !== $file['type'] ) {
			throw new Exception( __( 'The file must be a JSON.', 'wphb' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json_file = file_get_contents( $file['tmp_name'] );
		if ( ! $json_file ) {
			throw new Exception( __( 'There was an error getting the contents of the file.', 'wphb' ) );
		}

		$configs = json_decode( $json_file, true );
		if ( empty( $configs ) || ! is_array( $configs ) ) {
			throw new Exception( __( 'There was an error decoding the file.', 'wphb' ) );
		}

		// Make sure the config has a name and configs.
		if ( empty( $configs['name'] ) || empty( $configs['config'] ) ) {
			throw new Exception( __( 'The uploaded config must have a name and a set of settings. Please make sure the uploaded file is the correct one.', 'wphb' ) );
		}

		// Sanitize.
		$configs['config'] = array(
			'configs' => $configs['config']['configs'],
			// Let's re-create this to avoid differences between imported settings coming from other versions.
			'strings' => $this->format_config_to_display( $configs['config']['configs'] ),
		);

		if ( empty( $configs['config']['configs'] ) ) {
			throw new Exception( __( 'The provided configs list isn’t correct. Please make sure the uploaded file is the correct one.', 'wphb' ) );
		}

		// Don't keep these if they exist.
		unset( $configs['hub_id'] );
		unset( $configs['default'] );

		return $configs;
	}

	/**
	 * Remove non-unique settings.
	 *
	 * @since 3.0.1
	 *
	 * @param array $settings  Array of settings.
	 *
	 * @return array
	 */
	private function remove_non_unique_settings( $settings ) {
		// Asset optimization.
		if ( isset( $settings['minify'] ) ) {
			unset( $settings['minify'] );
		}

		// Uptime.
		if ( isset( $settings['uptime'] ) ) {
			if ( isset( $settings['uptime']['notifications'] ) ) {
				unset( $settings['uptime']['notifications'] );
			}
			if ( isset( $settings['uptime']['reports'] ) ) {
				unset( $settings['uptime']['reports'] );
			}
		}

		// Page caching.
		if ( isset( $settings['page_cache'] ) && isset( $settings['page_cache']['pages_cached'] ) ) {
			unset( $settings['page_cache']['pages_cached'] );
			if ( ! is_multisite() && ! is_network_admin() && isset( $settings['page_cache']['cache_blog'] ) ) {
				unset( $settings['page_cache']['cache_blog'] );
			}
		}

		// Browser caching.
		if ( isset( $settings['caching'] ) ) {
			unset( $settings['caching'] );
		}

		// Cloudflare.
		if ( isset( $settings['cloudflare'] ) ) {
			unset( $settings['cloudflare'] );
		}

		// Performance test.
		if ( isset( $settings['performance'] ) ) {
			if ( isset( $settings['performance']['reports'] ) ) {
				unset( $settings['performance']['reports'] );
			}
			if ( isset( $settings['performance']['dismissed'] ) ) {
				unset( $settings['performance']['dismissed'] );
			}
			if ( ! is_multisite() && ! is_network_admin() && isset( $settings['performance']['subsite_tests'] ) ) {
				unset( $settings['performance']['subsite_tests'] );
			}
		}

		// Advanced tools.
		if ( isset( $settings['advanced'] ) ) {
			if ( isset( $settings['advanced']['prefetch'] ) ) {
				unset( $settings['advanced']['prefetch'] );
			}
			if ( isset( $settings['advanced']['preconnect'] ) ) {
				unset( $settings['advanced']['preconnect'] );
			}
			if ( ! is_multisite() && ! is_network_admin() && isset( $settings['advanced']['query_strings_global'] ) ) {
				unset( $settings['advanced']['query_strings_global'] );
			}
			if ( ! is_multisite() && ! is_network_admin() && isset( $settings['advanced']['emoji_global'] ) ) {
				unset( $settings['advanced']['emoji_global'] );
			}
			if ( isset( $settings['advanced']['lazy_load'] ) && isset( $settings['advanced']['lazy_load']['button'] ) ) {
				unset( $settings['advanced']['lazy_load']['button'] );
			}
		}

		// Settings.
		if ( isset( $settings['settings'] ) && isset( $settings['settings']['tracking'] ) ) {
			unset( $settings['settings']['tracking'] );
		}

		// Redis.
		if ( isset( $settings['redis'] ) ) {
			unset( $settings['redis'] );
		}

		return $settings;
	}

	/**
	 * Remove settings for modules that are inactive.
	 *
	 * @since 3.0.1
	 *
	 * @param array $settings  Array of settings.
	 *
	 * @return array
	 */
	private function remove_disabled_options( $settings ) {
		if ( is_multisite() && is_network_admin() ) {
			$this->defaults['page_cache']['enabled'] = 'blog-admins';
		}

		if ( isset( $settings['advanced'] ) && isset( $settings['advanced']['lazy_load'] ) && isset( $settings['advanced']['lazy_load']['enabled'] ) && ! $settings['advanced']['lazy_load']['enabled'] ) {
			unset( $settings['advanced']['lazy_load']['method'] );
			unset( $settings['advanced']['lazy_load']['threshold'] );
		}

		if ( isset( $settings['page_cache'] ) && isset( $settings['page_cache']['enabled'] ) ) {
			if ( ! $settings['page_cache']['enabled'] ) {
				$settings['page_cache'] = array( 'enabled' => false );
			} elseif ( isset( $settings['page_cache']['preload'] ) && ! $settings['page_cache']['preload'] && isset( $settings['page_cache']['preload_type'] ) ) {
				unset( $settings['page_cache']['preload_type'] );
			}
		}

		return $settings;
	}

	/**
	 * Format config for display.
	 *
	 * @since 3.0.1
	 *
	 * @param array $config  Current config.
	 */
	private function format_config_to_display( $config ) {
		$display_values = array();

		if ( ! isset( $config['settings'] ) ) {
			return $display_values;
		}

		foreach ( $config['settings'] as $module => $settings ) {
			if ( ! is_array( $settings ) || empty( $settings ) ) {
				continue;
			}

			$display_values[ $module ] = array( $this->format_settings( $module, $settings ) );
		}

		return $display_values;
	}

	/**
	 * Format settings array to readable values.
	 *
	 * @since 3.0.1
	 *
	 * @param string $module    Module ID.
	 * @param array  $settings  Module settings.
	 *
	 * @return string
	 */
	private function format_settings( $module, $settings ) {
		$values = '';

		foreach ( $settings as $setting => $value ) {
			$result = $this->get_setting_description( $module, $setting, $value );

			if ( ! $result ) {
				continue;
			}

			$values .= $result;
		}

		return $values;
	}

	/**
	 * Format a setting value to a readable string.
	 *
	 * @since 3.0.1
	 *
	 * @param string      $module   Module ID.
	 * @param string      $setting  Setting name.
	 * @param bool|string $value    Setting value.
	 *
	 * @return string
	 */
	private function get_setting_description( $module, $setting, $value ) {
		$descriptions = array(
			'advanced'                => array(
				'query_string'         => __( 'Remove query strings from assets', 'wphb' ),
				'query_strings_global' => __( 'Remove query strings on all subsites', 'wphb' ),
				'emoji'                => __( 'Remove Emoji JS & CSS files', 'wphb' ),
				'emoji_global'         => __( 'Remove Emoji JS & CSS files on all subsites', 'wphb' ),
				'db_cleanups'          => __( 'Scheduled database cleanups', 'wphb' ), // TODO: add (int) db_frequency and (array) db_tables fields.
				'cart_fragments'       => __( 'Disable WooCommerce cart fragments', 'wphb' ),
			),
			'advanced_lazy_load'      => array(
				'enabled'   => __( 'Comments lazy loading', 'wphb' ),
				'method'    => __( 'Comments lazy loading method', 'wphb' ),
				'threshold' => __( 'Threshold', 'wphb' ),
			),
			'gravatar'                => array(
				'enabled' => __( 'Gravatar cache', 'wphb' ),
			),
			'page_cache'              => array(
				'enabled'    => __( 'Page cache', 'wphb' ),
				'cache_blog' => __( 'Allow subsites to disable page caching', 'wphb' ),
				'detection'  => __( 'File change detection', 'wphb' ),
				'preload'    => __( 'Cache preloading', 'wphb' ),
			),
			'page_cache_integrations' => array(
				'varnish' => __( 'Purge Varnish cache', 'wphb' ),
				'opcache' => __( 'Purge OpCache', 'wphb' ),
			),
			'page_cache_preload_type' => array(
				'home_page' => __( 'Preload homepage', 'wphb' ),
				'on_clear'  => __( "Preload any page or post that's been updated, or for which the cache was cleared", 'wphb' ),
			),
			'performance'             => array(
				'subsite_tests' => __( 'Performance tests on subsites', 'wphb' ),
			),

			'rss'                     => array(
				'enabled'  => __( 'RSS caching', 'wphb' ),
				'duration' => __( 'Expiry time', 'wphb' ),
			),
			'settings'                => array(
				'accessible_colors' => __( 'High contrast mode', 'wphb' ),
				'remove_settings'   => __( 'Remove settings on uninstall', 'wphb' ),
				'remove_data'       => __( 'Remove data on uninstall', 'wphb' ),
				'control'           => __( 'Cache control in admin bar', 'wphb' ),
			),
			'uptime'                  => array(
				'enabled' => __( 'Uptime', 'wphb' ),
			),
		);

		// Overwrites for non common values.
		if ( 'page_cache' === $module ) {
			if ( 'enabled' === $setting && 'blog-admins' === $value ) {
				$value = true;
			}

			if ( 'cache_blog' === $setting ) {
				$cache_status = Settings::get_setting( 'enabled', 'page_cache' );
				$value_string = 'blog-admins' === $cache_status ? __( 'Active', 'wphb' ) : __( 'Inactive', 'wphb' );
				return $descriptions[ $module ][ $setting ] . ' - ' . $value_string . PHP_EOL;
			}
		}

		// Loop over early.
		if ( is_array( $value ) ) {
			$submodule = $module . '_' . $setting;

			$strings = '';
			foreach ( $value as $el => $val ) {
				$strings .= $this->get_setting_description( $submodule, $el, $val );
			}

			return $strings;
		}

		// No setting and not array - exit.
		if ( ! isset( $descriptions[ $module ] ) || ! isset( $descriptions[ $module ][ $setting ] ) ) {
			return '';
		}

		if ( is_bool( $value ) ) {
			$value_string = $value ? __( 'Active', 'wphb' ) : __( 'Inactive', 'wphb' );
			return $descriptions[ $module ][ $setting ] . ' - ' . $value_string . PHP_EOL;
		}

		// Settings in page caching module.
		if ( '1' === $value || '0' === $value ) {
			$value_string = '1' === $value ? __( 'Active', 'wphb' ) : __( 'Inactive', 'wphb' );
			return $descriptions[ $module ][ $setting ] . ' - ' . $value_string . PHP_EOL;
		}

		if ( is_string( $value ) ) {
			return $descriptions[ $module ][ $setting ] . ' - ' . ucfirst( $value ) . PHP_EOL;
		}

		return '';
	}

}
