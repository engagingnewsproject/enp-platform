<?php

use WPMUDEV_Analytics_Vendor\Mixpanel;

if ( ! class_exists( 'WPMUDEV_Analytics_V4' ) ) {
	/**
	 * @method identify( int $user_id )
	 * @method register( string $property, mixed $value )
	 * @method registerAll( array $properties )
	 */
	class WPMUDEV_Analytics_V4 {
		private $data_option_id = 'wpmudev_analytics_%s_json_data';
		private $exceeded_event_name = 'exceeded_daily_limit';

		private $plugin_slug;
		private $plugin_name;
		private $event_limit;
		private $project_token;
		/**
		 * @var Mixpanel
		 */
		private $mixpanel;
		/**
		 * @var int
		 */
		private $time_window;
		/**
		 * @var array
		 */
		private $options;
		/**
		 * @var mixed|string|null
		 */
		private $mysql_version;

		public function __construct( $plugin_slug, $plugin_name, $event_limit, $project_token, $options = array() ) {
			$this->plugin_slug   = $plugin_slug;
			$this->plugin_name   = $plugin_name;
			$this->project_token = $project_token;
			$this->options       = empty( $options ) ? array() : $options;

			$this->time_window = $this->get_constant_value( 'WPMUDEV_ANALYTICS_TIME_WINDOW_SECONDS', HOUR_IN_SECONDS * 24 );
			$this->event_limit = $this->get_constant_value( 'WPMUDEV_ANALYTICS_EVENT_LIMIT', $event_limit );
		}

		private function get_consumer() {
			return $this->get_array_value( $this->options, 'consumer', 'curl' );
		}

		private function get_constant_value( $name, $default ) {
			return defined( $name ) && ! empty( constant( $name ) )
				? (int) constant( $name )
				: $default;
		}

		/**
		 * @return Mixpanel|null
		 */
		private function get_mixpanel() {
			if ( ! $this->server_meets_requirements() ) {
				return null;
			}

			if ( is_null( $this->mixpanel ) ) {
				$this->mixpanel = $this->prepare_mixpanel_instance();
			}

			return $this->mixpanel;
		}

		public function set_mixpanel( $mixpanel ) {
			$this->mixpanel = $mixpanel;
		}

		private function prepare_mixpanel_instance() {
			$options = array_merge(
				$this->options,
				array( 'error_callback' => array( $this, 'handle_error' ) )
			);

			return Mixpanel::getInstance( $this->project_token, $options );
		}

		public function handle_error( $code, $data ) {
			$plugin_name = $this->plugin_name;

			error_log( "$plugin_name: $code: $data" );
		}

		public function __call( $name, $arguments ) {
			$mixpanel = $this->get_mixpanel();
			if ( $mixpanel && method_exists( $mixpanel, $name ) ) {
				return call_user_func_array(
					array( $mixpanel, $name ),
					$arguments
				);
			}

			return null;
		}

		private function server_meets_requirements() {
			$required_functions = array();
			if ( $this->get_consumer() === 'socket' ) {
				$required_functions = array( 'pfsockopen' );
			} else if ( $this->get_consumer() === 'curl' ) {
				$required_functions = array( 'curl_init' );
			}

			foreach ( $required_functions as $function_name ) {
				if ( ! $this->is_function_supported( $function_name ) ) {
					return false;
				}
			}

			return true;
		}

		public function track( $event, $properties = array() ) {
			$event_count = $this->update_data();
			$mixpanel    = $this->get_mixpanel();
			if ( ! $mixpanel ) {
				return;
			}

			if ( $event_count < $this->event_limit ) {
				$mixpanel->track( $event, $properties );
			} else if ( $event_count === $this->event_limit ) {
				$mixpanel->track( $this->exceeded_event_name, array(
					'Limit' => $this->event_limit,
				) );
			}
		}

		/**
		 * @return string
		 */
		private function get_option_key() {
			return sprintf( $this->data_option_id, $this->plugin_slug );
		}

		private function update_data() {
			if ( version_compare( $this->get_mysql_version(), '5.7', '<' ) ) {
				return $this->wp_update_data();
			} else {
				return $this->update_json_data();
			}
		}

		private function update_json_data() {
			global $wpdb;

			$table        = $wpdb->options;
			$column       = 'option_name';
			$value_column = 'option_value';
			$option_key   = $this->get_option_key();

			$time_now    = $this->get_time_now();
			$time_window = $this->time_window;
			$inserted    = $wpdb->query( "
				INSERT IGNORE INTO {$table}
				SET `$column` = '$option_key',
					`$value_column` = '{}';
			" );
			$updated     = $wpdb->query( "
				UPDATE {$table}
				SET
				  {$value_column} = CASE
				      WHEN COALESCE(JSON_EXTRACT({$value_column}, '$.timestamp'), 0) + {$time_window} < {$time_now} THEN
				        JSON_SET({$value_column}, '$.timestamp', {$time_now}, '$.event_count', 0)
				      ELSE
				        JSON_SET({$value_column}, '$.event_count', COALESCE(JSON_EXTRACT({$value_column}, '$.event_count'), 0) + 1)
				    END
				WHERE {$column} = '{$option_key}';
			" );
			$event_count = (int) $wpdb->get_var( "SELECT COALESCE(JSON_EXTRACT({$value_column}, '$.event_count'), 0) AS extracted_value FROM {$table} WHERE {$column} = '{$option_key}';" );

			return $event_count;
		}

		private function get_time_now() {
			$time_function = isset( $this->options['time_function'] )
				? $this->options['time_function']
				: 'time';

			return call_user_func( $time_function );
		}

		private function wp_update_data() {
			$timestamp = $this->wp_get_timestamp();
			$exceeded  = $timestamp + $this->time_window < $this->get_time_now();
			if ( $exceeded ) {
				$new_event_count = 0;
				$this->wp_set_data( array(
					'timestamp'   => $this->get_time_now(),
					'event_count' => $new_event_count,
				) );
			} else {
				$new_event_count = $this->wp_get_event_count() + 1;
				$this->wp_set_data( array(
					'timestamp'   => $timestamp,
					'event_count' => $new_event_count,
				) );
			}
			return $new_event_count;
		}

		private function wp_get_event_count() {
			$data = $this->wp_get_data();
			return isset( $data['event_count'] )
				? (int) $data['event_count']
				: 0;
		}

		private function wp_get_timestamp() {
			$data = $this->wp_get_data();
			return isset( $data['timestamp'] )
				? (int) $data['timestamp']
				: 0;
		}

		private function wp_get_data() {
			return json_decode( get_option( $this->get_option_key() ), true );
		}

		private function wp_set_data( $data ) {
			if ( empty( $data ) ) {
				delete_option( $this->get_option_key() );
			} else {
				update_option( $this->get_option_key(), json_encode( $data ), false );
			}
		}

		private function get_mysql_version() {
			if ( ! $this->mysql_version ) {
				global $wpdb;
				/**
				 * MariaDB version prefix 5.5.5- is not stripped when using $wpdb->db_version() to get the DB version:
				 * https://github.com/php/php-src/issues/7972
				 */
				$this->mysql_version = $wpdb->get_var( 'SELECT VERSION()' );
			}
			return $this->mysql_version;
		}

		public function set_mysql_version( $mysql_version ) {
			$this->mysql_version = $mysql_version;
		}

		private function is_function_supported( $function_name ) {
			if ( ! function_exists( $function_name ) ) {
				return false;
			}

			$disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
			if ( in_array( $function_name, $disabled_functions ) ) {
				return false;
			}

			return true;
		}

		public function get_array_value( $haystack, $key, $default_value = null ) {
			if ( empty( $key ) ) {
				return $default_value;
			}

			if ( ! is_array( $key ) ) {
				$key = array( $key );
			}

			if ( ! is_array( $haystack ) ) {
				return $default_value;
			}

			$value = $haystack;
			foreach ( $key as $key_part ) {
				$value = isset( $value[ $key_part ] ) ? $value[ $key_part ] : $default_value;
			}

			return $value;
		}
	}
}