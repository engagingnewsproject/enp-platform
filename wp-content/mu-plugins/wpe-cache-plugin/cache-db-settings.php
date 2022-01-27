<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/security/security-checks.php';
require_once __DIR__ . '/cache-setting-values.php';

\wpengine\cache_plugin\check_security();

class CacheDbSettings {
	private static $instance        = null;
	const CONFIG_OPTION             = 'wpe_cache_config';
	const SETTINGS_GROUP            = 'wpengine-cache-control';
	const CACHE_LAST_CLEARED_OPTION = 'wpe_cache_last_cleared';
	const CACHE_LAST_ERROR_OPTION   = 'wpe_cache_last_cleared_error';
	const DEFAULT_DATE_VALUE        = '2000-01-01';

	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::CONFIG_OPTION,
			array(
				'sanitize_callback' => array(
					$this,
					'validate_cache_control_settings',
				),
			)
		);
	}

	public function update( $key, $value ) {
		$options         = self::get();
		$options[ $key ] = $value;
		update_option( self::CONFIG_OPTION, $options );
	}

	public function get_cache_last_cleared() {
		return $this->get_date_option_value_or_default( self::CACHE_LAST_CLEARED_OPTION );
	}

	public function update_cache_last_cleared() {
		return $this->update_date_option_with_now( self::CACHE_LAST_CLEARED_OPTION );
	}

	public function get_cache_last_error() {
		return $this->get_date_option_value_or_default( self::CACHE_LAST_ERROR_OPTION );
	}

	public function update_cache_last_error() {
		return $this->update_date_option_with_now( self::CACHE_LAST_ERROR_OPTION );
	}

	public function get( $opt = null ) {
		$options = get_option( self::CONFIG_OPTION );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options = wp_parse_args(
			$options,
			array(
				'sanitized_post_types'         => array(),
				'sanitized_builtin_post_types' => array(),
				'smarter_cache_enabled'        => 0,
				'last_modified_enabled'        => 0,
				'wpe_ac_global_last_modified'  => CacheSettingValues::FORTY_YEARS_IN_SECONDS,
			)
		);
		if ( isset( $opt ) ) {
			$option = isset( $options[ $opt ] ) ? $options[ $opt ] : null;
			return $option;
		} else {
			return $options;
		}
	}

	public function get_rest_api_namespaces() {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return;
		}
		$server = rest_get_server();
		return $server->get_namespaces();
	}

	public function validate_cache_control_settings( $options ) {
		$current = $this->get();
		if ( ! is_array( $options ) ) {
			return $current;
		}
		$validations = $this->get_validations_array();
		$options     = filter_var_array( $options, $validations );
		return $options;
	}

	public function get_validations_array() {
		$sanitized_post_types = $this->get_sanitized_post_types();
		$validations          = array(
			'sanitized_post_types'         => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FORCE_ARRAY,
			),
			'sanitized_builtin_post_types' => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FORCE_ARRAY,
			),
			'smarter_cache_enabled'        => FILTER_SANITIZE_STRING,
			'last_modified_enabled'        => FILTER_SANITIZE_STRING,
			'wpe_ac_global_last_modified'  => FILTER_SANITIZE_STRING,
		);
		foreach ( $sanitized_post_types as $post_type ) {
			$validations[ $post_type . '_cache_expires_value' ] = FILTER_SANITIZE_STRING;
		}
		if ( function_exists( 'rest_get_server' ) ) {
			$namespaces                = $this->get_rest_api_namespaces();
			$validations['namespaces'] = array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FORCE_ARRAY,
			);
			foreach ( $namespaces as $namespace ) {
				$validations[ $namespace . '_cache_expires_value' ] = FILTER_SANITIZE_STRING;
			}
		}
		return $validations;
	}

	private function get_sanitized_post_types( $builtin = false ) {
		$args = array(
			'public' => true,
		);
		if ( true === $builtin ) {
			$args['_builtin'] = true;
		}
		$post_types = get_post_types( $args, 'names' );

		$post_types = array_diff( $post_types, array( 'revision', 'nav_menu_item', 'attachment' ) );

		return apply_filters( 'wpe_ac_get_sanitized_post_types', $post_types );
	}

	public function init_settings() {
		if ( function_exists( 'rest_get_server' ) ) {
			$namespaces = $this->get_rest_api_namespaces();
			foreach ( $namespaces as $namespace ) {
				$this->init_cache_control_settings( $namespace );
			}
			$this->update( 'namespaces', $this->get_rest_api_namespaces() );
		}

		foreach ( $this->get_sanitized_post_types() as $post_type ) {
			$this->init_cache_control_settings( $post_type );
		}

		$this->update( 'sanitized_post_types', $this->get_sanitized_post_types() );
		$this->update( 'sanitized_builtin_post_types', $this->get_sanitized_post_types( true ) );

	}

	private function init_cache_control_settings( $post_type ) {
		$key = $post_type . '_cache_expires_value';
		if ( empty( $this->get( $key ) ) ) {
			$this->set_to_default_value( $post_type );
		}
	}

	private function set_to_default_value( $post_type ) {
			$this->update( $post_type . '_cache_expires_value', CacheSettingValues::SETTING_DEFAULT_VALUE );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new CacheDbSettings();
		}

		return self::$instance;
	}

	private function update_date_option_with_now( $option_value ) {
		$now = gmdate( 'Y-m-d H:i:s' );
		update_option( $option_value, $now );

		return $now;
	}

	public function cache_db_settings_get_option( $option_value ) {
		return get_option( $option_value );
	}

	private function get_date_option_value_or_default( $option_value ) {
		$date = $this->cache_db_settings_get_option( $option_value );
		if ( ! $date ) {
			return self::DEFAULT_DATE_VALUE;
		}
		$date_string = strval( $date );
		return strtotime( $date_string ) ? $date_string : self::DEFAULT_DATE_VALUE;
	}
}
