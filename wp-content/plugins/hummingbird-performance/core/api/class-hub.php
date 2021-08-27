<?php
/**
 * Hummingbird - Hub endpoints: Hub class
 *
 * Manage WPMU DEV Hub API endpoints
 *
 * @package Hummingbird\Core\Api
 */

namespace Hummingbird\Core\Api;

use Hummingbird\Core\Configs;
use Hummingbird\Core\Modules\Performance;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use Hummingbird\WP_Hummingbird;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Hub
 */
class Hub {

	/**
	 * Endpoints array.
	 *
	 * @var array
	 */
	private $endpoints = array(
		'get',
		'performance',
		'enable',
		'disable',
		'schedule',
		'unschedule',
		'clear_cache',
		'get_timezone',
		'recipients',
		'purge_all_cache',
		'import_settings',
		'export_settings',
	);

	/**
	 * Hub constructor.
	 */
	public function __construct() {
		add_filter( 'wdp_register_hub_action', array( $this, 'add_endpoints' ) );
	}

	/**
	 * Add Hub endpoints
	 *
	 * Every Hub Endpoint name is build following the structure: 'wphb-$endpoint-$action'
	 * Examples:
	 * wphb-browser-caching-get
	 * wphb-gzip-get
	 *
	 * @param array $actions  Endpoint action.
	 *
	 * @return array
	 */
	public function add_endpoints( $actions ) {
		foreach ( $this->endpoints as $endpoint ) {
			$actions[ "wphb-{$endpoint}" ] = array( $this, 'action_' . $endpoint );
		}

		return $actions;
	}

	/**
	 * Retrieve data for endpoint.
	 *
	 * @param array  $params  Parameters.
	 * @param string $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_get( $params, $action ) {
		$result = array();

		/**
		 * Gzip
		 */
		$module = Utils::get_module( 'gzip' );
		$module->get_analysis_data();
		$status = $module->status;

		if ( ! is_array( $status ) ) {
			$result['gzip'] = new WP_Error( 'gzip-status-not-found', 'There is not Gzip data yet' );
		} else {
			$result['gzip'] = array();
			foreach ( $status as $status_name => $status_value ) {
				$result['gzip']['status'][ strtolower( $status_name ) ] = $status_value;
			}
			$result['gzip']['server'] = $module::get_server_type( false );
		}

		/**
		 * Caching
		 */
		$module = Utils::get_module( 'caching' );
		$module->get_analysis_data();
		$status = $module->status;

		if ( ! is_array( $status ) ) {
			$result['browser-caching'] = new WP_Error( 'browser-caching-status-not-found', 'There is not Browser Caching data yet' );
		} else {
			// We have a Cloudflare connection.
			if ( Utils::get_module( 'cloudflare' )->is_connected() && Utils::get_module( 'cloudflare' )->is_zone_selected() ) {
				$status = array_fill_keys( array_keys( $status ), Utils::get_module( 'cloudflare' )->get_caching_expiration() );
			}

			$result['browser-caching'] = array();
			foreach ( $status as $status_name => $status_value ) {
				$result['browser-caching']['status'][ strtolower( $status_name ) ] = $status_value;
			}
		}

		/**
		 * Gravatar
		 */
		$module = Utils::get_module( 'gravatar' );

		$result['gravatar']['is_active'] = $module->is_active();
		$result['gravatar']['error']     = is_wp_error( $module->error );

		/**
		 * Asset Optimization
		 */
		$module = Utils::get_module( 'minify' );

		$collection = $module->get_resources_collection();
		if ( empty( $collection ) ) {
			$result['minify'] = new WP_Error( 'minify-status-not-found', 'There is no Asset Optimization data yet' );
		} elseif ( ! $module->is_active() ) {
			$result['minify'] = new WP_Error( 'minify-disabled', 'Asset Optimization module not activated' );
		} else {
			// Remove those assets that we don't want to display.
			foreach ( $collection['styles'] as $key => $item ) {
				if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'styles' ) || ! isset( $item['original_size'], $item['compressed_size'] ) ) {
					unset( $collection['styles'][ $key ] );
				}
			}
			foreach ( $collection['scripts'] as $key => $item ) {
				if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'scripts' ) || ! isset( $item['original_size'], $item['compressed_size'] ) ) {
					unset( $collection['scripts'][ $key ] );
				}
			}

			$original_size_styles  = Utils::calculate_sum( wp_list_pluck( $collection['styles'], 'original_size' ) );
			$original_size_scripts = Utils::calculate_sum( wp_list_pluck( $collection['scripts'], 'original_size' ) );
			$original_size         = $original_size_scripts + $original_size_styles;

			$compressed_size_styles  = Utils::calculate_sum( wp_list_pluck( $collection['styles'], 'compressed_size' ) );
			$compressed_size_scripts = Utils::calculate_sum( wp_list_pluck( $collection['scripts'], 'compressed_size' ) );
			$compressed_size         = $compressed_size_scripts + $compressed_size_styles;

			if ( ( $original_size_scripts + $original_size_styles ) <= 0 ) {
				$percentage = 0;
			} else {
				$percentage = 100 - (int) $compressed_size * 100 / (int) $original_size;
			}

			$compressed_size_scripts = number_format( $original_size_scripts - $compressed_size_scripts, 0 );
			$compressed_size_styles  = number_format( $original_size_styles - $compressed_size_styles, 0 );

			$result['minify']['status']['files']      = count( $collection['scripts'] ) + count( $collection['styles'] );
			$result['minify']['status']['original']   = number_format( $original_size, 1 );
			$result['minify']['status']['compressed'] = number_format( $compressed_size, 1 );
			$result['minify']['status']['percent']    = number_format_i18n( $percentage, 1 );
			$result['minify']['status']['saved_js']   = $compressed_size_scripts;
			$result['minify']['status']['saved_css']  = $compressed_size_styles;
			$result['minify']['status']['cdn']        = $module->get_cdn_status();
		}

		/**
		 * Page caching
		 */
		$module = Utils::get_module( 'page_cache' );

		$result['page-caching']['status'] = $module->is_active();
		$result['page-caching']['error']  = $module->error;

		/**
		 * RSS caching
		 */
		$module  = Utils::get_module( 'rss' );
		$options = $module->get_options();

		$result['rss-caching']['status']   = $options['enabled'];
		$result['rss-caching']['duration'] = $options['duration'];

		/**
		 * Database cleanup.
		 *
		 * @since 2.5.1
		 */
		$module  = Utils::get_module( 'advanced' );
		$options = $module->get_options();

		$result['db-cleanup']['status']    = $options['db_cleanups'];
		$result['db-cleanup']['frequency'] = isset( $options['db_frequency'] ) ? $options['db_frequency'] : 7;

		/**
		 * Advanced tools.
		 *
		 * @since 2.5.1
		 */
		$result['advanced']['query_string']   = $options['query_string'];
		$result['advanced']['emoji']          = $options['emoji'];
		$result['advanced']['cart_fragments'] = $options['cart_fragments'];

		/**
		 * Reports
		 */
		$performance_module    = Utils::get_module( 'performance' );
		$options               = $performance_module->get_options();
		$performance_is_active = isset( $options['reports']['enabled'] ) ? $options['reports']['enabled'] : false;

		$uptime_is_active     = Utils::get_module( 'uptime' )->is_active();
		$uptime_reporting     = Settings::get_setting( 'reports', 'uptime' );
		$uptime_notifications = Settings::get_setting( 'notifications', 'uptime' );

		$result['uptime']['reports']       = $uptime_reporting;
		$result['uptime']['notifications'] = $uptime_notifications;

		$frequency = '';
		if ( $performance_is_active ) {
			$frequency = $options['reports']['frequency'];
			switch ( $frequency ) {
				case 1:
					$frequency = __( 'Daily', 'wphb' );
					break;
				case 7:
				default:
					$frequency = __( 'Weekly', 'wphb' );
					break;
				case 30:
					$frequency = __( 'Monthly', 'wphb' );
					break;
			}

			$result['reports']['performance']['day']  = $options['reports']['day'];
			$result['reports']['performance']['time'] = $options['reports']['time'];
		}

		$result['reports']['performance']['performance_is_active'] = $performance_is_active;
		$result['reports']['uptime']['uptime_is_active']           = $uptime_is_active;
		$result['reports']['uptime']['frequency']                  = $frequency;

		$result = (object) $result;
		wp_send_json_success( $result );
	}

	/**
	 * Update performance scan from the Hub.
	 *
	 * @since 1.6.1
	 * @param array  $params  Parameters.
	 * @param string $action  Action.
	 */
	public function action_performance( $params, $action ) {
		// Refresh report if run from the Hub.
		Performance::refresh_report();
	}

	/**
	 * Enable modules from the Hub.
	 *
	 * @since 1.9.1
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_enable( $params, $action ) {
		$module = Utils::get_module( $params->module );

		if ( ! $module ) {
			wp_send_json_error(
				array(
					'message' => __( "Hummingbird module doesn't exist.", 'wphb' ),
				)
			);
		}

		if ( ! method_exists( $module, 'enable' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Enabling this module remotely is not possible.', 'wphb' ),
				)
			);
		}

		define( 'WPHB_IS_NETWORK_ADMIN', true );
		call_user_func( array( $module, 'enable' ) );
		wp_send_json_success();
	}

	/**
	 * Disable modules from the Hub.
	 *
	 * @since 1.9.1
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_disable( $params, $action ) {
		$module = Utils::get_module( $params->module );

		if ( ! $module ) {
			wp_send_json_error(
				array(
					'message' => __( "Hummingbird module doesn't exist.", 'wphb' ),
				)
			);
		}

		if ( ! method_exists( $module, 'disable' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Disabling this module remotely is not possible.', 'wphb' ),
				)
			);
		}

		define( 'WPHB_IS_NETWORK_ADMIN', true );
		call_user_func( array( $module, 'disable' ) );
		wp_send_json_success();
	}

	/**
	 * Schedule performance reports.
	 *
	 * @since 1.9.3
	 *
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 */
	public function action_schedule( $params, $action ) {
		if ( ! Utils::is_member() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error getting membership status', 'wphb' ),
				)
			);
		}

		$available_modules = array(
			'performance'   => 'performance',
			'notifications' => 'uptime',
			'reports'       => 'uptime',
		);

		$module = isset( $params->module ) ? $params->module : 'performance';

		// Make sure modules cache can be cleared.
		if ( ! in_array( $module, array_keys( $available_modules ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested module was invalid.', 'wphb' ),
				)
			);
		}

		$reports = Utils::get_module( $available_modules[ $module ] );
		$options = $reports->get_options();

		// Randomize the minutes, so we don't spam the API.
		$email_time    = explode( ':', sanitize_text_field( $params->time ) );
		$email_time[1] = sprintf( '%02d', wp_rand( 0, 59 ) );

		if ( 'performance' === $module || 'reports' === $module ) {
			$options['reports']['enabled']   = true;
			$options['reports']['frequency'] = (int) $params->frequency;
			$options['reports']['day']       = sanitize_text_field( $params->day );
			$options['reports']['time']      = implode( ':', $email_time );
			$options['reports']['last_sent'] = '';
		} elseif ( 'notifications' === $module ) {
			$options[ $module ]['enabled']   = true;
			$options[ $module ]['threshold'] = isset( $params->threshold ) ? (int) $params->threshold : 0;
		}

		$reports->update_options( $options );

		if ( 'performance' === $module || 'reports' === $module ) {
			// It's either uptime or performance in the cron schedules.
			$module = 'reports' === $module ? 'uptime' : $module;

			// Clean all cron.
			wp_clear_scheduled_hook( "wphb_{$module}_report" );

			// Reschedule.
			$next_scan_time = \Hummingbird\Core\Pro\Modules\Reports::get_scheduled_time( $module, false );
			wp_schedule_single_event( $next_scan_time, "wphb_{$module}_report" );
		}

		wp_send_json_success();
	}

	/**
	 * Unschedule performance reports.
	 *
	 * @since 1.9.4
	 *
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 */
	public function action_unschedule( $params, $action ) {
		if ( ! Utils::is_member() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error getting membership status', 'wphb' ),
				)
			);
		}

		$available_modules = array(
			'performance'   => 'performance',
			'notifications' => 'uptime',
			'reports'       => 'uptime',
		);

		$module = isset( $params->module ) ? $params->module : 'performance';

		// Make sure modules cache can be cleared.
		if ( ! in_array( $module, array_keys( $available_modules ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested module was invalid.', 'wphb' ),
				)
			);
		}

		$reports = Utils::get_module( $available_modules[ $module ] );
		$options = $reports->get_options();

		if ( 'performance' === $module || 'reports' === $module ) {
			$options['reports']['enabled'] = false;
			// Clean all cron.
			wp_clear_scheduled_hook( "wphb_{$module}_report" );
		} elseif ( 'notifications' === $module ) {
			$options[ $module ]['enabled'] = false;
		}

		$reports->update_options( $options );

		wp_send_json_success();
	}

	/**
	 * Clears cache for modules from the Hub.
	 *
	 * @since 1.9.3
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_clear_cache( $params, $action ) {
		$module = $params->module;

		$available_modules = array(
			'page_cache',
			'performance',
			'gravatar',
			'minify',
			'cloudflare',
		);

		if ( ! $module ) {
			wp_send_json_error(
				array(
					'message' => __( "Hummingbird module doesn't exist.", 'wphb' ),
				)
			);
		}

		// Make sure modules cache can be cleared.
		if ( ! in_array( $module, $available_modules, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested module was invalid.', 'wphb' ),
				)
			);
		}

		// Make sure module is active.
		if ( ! Utils::get_module( $module )->is_active() ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested module is inactive.', 'wphb' ),
				)
			);
		}

		// Clear the cache of module.
		switch ( $module ) {
			case 'minify':
				$response = array(
					'cache_cleared' => Utils::get_module( $module )->clear_cache( false ),
				);
				break;
			default:
				$response = array(
					'cache_cleared' => Utils::get_module( $module )->clear_cache(),
				);
				break;
		}
		wp_send_json_success( $response );
	}


	/**
	 * Clears cache for modules from the Hub.
	 *
	 * @since 1.9.3
	 * @param array  $params  Parameters.
	 * @param string $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_get_timezone( $params, $action ) {
		$result = array(
			'timezone'     => get_option( 'timezone_string' ),
			'offset'       => get_option( 'gmt_offset' ),
			'current_time' => current_time( 'mysql' ),
		);

		$result = (object) $result;
		wp_send_json_success( $result );
	}

	/**
	 * Recipients actions.
	 *
	 * @since 1.9.3
	 *
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action. Accepts: get, set.
	 */
	public function action_recipients( $params, $action ) {
		if ( ! Utils::is_member() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error getting membership status', 'wphb' ),
				)
			);
		}

		$available_modules = array(
			'performance'   => 'performance',
			'notifications' => 'uptime',
			'reports'       => 'uptime',
		);

		$module = $params->module;

		// Make sure modules cache can be cleared.
		if ( ! in_array( $module, array_keys( $available_modules ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested module was invalid.', 'wphb' ),
				)
			);
		}

		$options = Settings::get_settings( $available_modules[ $module ] );

		// If we are setting the recipients.
		if ( isset( $params->action ) && 'set' === $params->action ) {
			$recipients = $params->recipients;
			$recipients = json_decode( wp_json_encode( $recipients ), true );

			// Make sure we have an array of recipients.
			if ( ! is_array( $recipients ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'No recipients defined.', 'wphb' ),
					)
				);
			}

			if ( 'notifications' === $module || 'reports' === $module ) {
				$options[ $module ]['recipients'] = $recipients;
			} else {
				$options['reports']['recipients'] = $recipients;
			}

			Settings::update_settings( $options, $available_modules[ $module ] );

			wp_send_json_success(
				array(
					'message' => __( 'Recipients updated', 'wphb' ),
				)
			);
		}

		// Default action is to get the recipients.
		if ( 'notifications' === $module || 'reports' === $module ) {
			$options = $options[ $module ];
		} else {
			$options = $options['reports'];
		}

		wp_send_json_success(
			array(
				'recipients' => $options['recipients'],
			)
		);
	}

	/**
	 * Purge all caches.
	 *
	 * @since 2.1.0
	 * @param array|object $params  Parameters.
	 * @param string       $action  Action.
	 *
	 * @return void|WP_Error
	 */
	public function action_purge_all_cache( $params, $action ) {
		WP_Hummingbird::flush_cache( false, false );
		wp_send_json_success();
	}

	/**
	 * Applies the given config sent by the Hub via the Dashboard plugin.
	 *
	 * @since 3.0.1
	 *
	 * @param object $params  The config sent by the Hub.
	 */
	public function action_import_settings( $params ) {
		if ( empty( $params->configs ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing config data', 'wphb' ),
				)
			);
		}

		// The Hub returns an object, we use an array.
		$config_array = json_decode( wp_json_encode( $params->configs ), true );

		$configs = new Configs();
		$configs->apply_config( $config_array );

		wp_send_json_success();
	}

	/**
	 * Exports the current settings as a config for the Hub.
	 *
	 * @since 3.0.1
	 */
	public function action_export_settings() {
		$configs = new Configs();
		$config  = $configs->get_config_from_current();

		wp_send_json_success( $config['config'] );
	}

}
