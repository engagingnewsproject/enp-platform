<?php
/**
 * Class Settings manages common settings for modules.
 *
 * @package Hummingbird\Core
 * @since 1.8
 */

namespace Hummingbird\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Plugin instance.
	 *
	 * @var Settings
	 */
	private static $instance;

	/**
	 * List of available modules.
	 *
	 * @since 1.8
	 *
	 * @var array
	 */
	private static $available_modules = array(
		'minify',
		'page_cache',
		'performance',
		'uptime',
		'gravatar',
		'caching',
		'cloudflare',
		'advanced',
		'rss',
		'settings',
		'redis',
	);

	/**
	 * List of network modules that have settings for each sub-site.
	 *
	 * @since 1.8
	 *
	 * @var array
	 */
	private static $network_modules = array( 'caching', 'minify', 'page_cache', 'performance', 'advanced', 'cloudflare' );

	/**
	 * Return the plugin instance.
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Settings constructor.
	 */
	private function __construct() {
	}

	/**
	 * Return the plugin default settings.
	 *
	 * @return array  Default Hummingbird settings.
	 */
	public static function get_default_settings() {
		$defaults = array(
			'minify'      => array(
				'enabled'      => false,
				'use_cdn'      => true,
				'log'          => false,
				'file_path'    => '',
				// Only for multisites. Toggles minification in a subsite
				// By default is true as if 'minify'-'enabled' is set to false, this option has no meaning.
				'minify_blog'  => false,
				'view'         => 'basic', // Accepts: 'basic' or 'advanced'.
				'type'         => 'speedy', // Accepts: 'speedy' or 'basic'.
				'do_assets'    => array( // Assets to optimize.
					'styles'  => true,
					'scripts' => true,
				),
				// Only for multisite.
				'block'        => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'dont_minify'  => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'dont_combine' => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'position'     => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'defer'        => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'inline'       => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'nocdn'        => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'fonts'        => array(),
				'preload'      => array(
					'scripts' => array(),
					'styles'  => array(),
				),
				'async'        => array(
					'scripts' => array(),
					'styles'  => array(),
				),
			),
			'uptime'      => array(
				'enabled'       => false,
				'notifications' => array(
					'enabled' => false,
				),
				'reports'       => array(
					'enabled' => false,
				),
			),
			'gravatar'    => array(
				'enabled' => false,
			),
			'page_cache'  => array(
				'enabled'      => false,
				// Only for multisites. Toggles page caching in a subsite
				// By default is true as if 'page_cache'-'enabled' is set to false, this option has no meaning.
				'cache_blog'   => true,
				'detection'    => 'auto', // Accepts: manual, auto and none.
				'pages_cached' => 0,
				'integrations' => array(
					'varnish' => false,
					'opcache' => false,
				),
				'preload'      => false,
				'preload_type' => array(
					'home_page' => true,
					'on_clear'  => true,
				),
			),
			'caching'     => array(
				// Always enabled, so no 'enabled' option.
				'expiry_css'        => '1y/A31536000',
				'expiry_javascript' => '1y/A31536000',
				'expiry_media'      => '1y/A31536000',
				'expiry_images'     => '1y/A31536000',
			),
			'cloudflare'  => array(
				'enabled'      => false,
				'connected'    => false,
				'last_check'   => false,
				'email'        => '',
				'api_key'      => '',
				'account_id'   => '',
				'zone'         => '',
				'zone_name'    => '',
				'plan'         => false,
				'page_rules'   => array(),
				'cache_expiry' => 31536000,
				'apo_paid'     => false,
				'apo'          => array(),
			),
			'performance' => array(
				'reports'       => array(
					'enabled' => false,
				),
				'subsite_tests' => true,
				'dismissed'     => false,
			),
			'advanced'    => array(
				'query_string'         => false,
				'query_strings_global' => false, // If true, will force query_string on all subsites.
				'emoji'                => false,
				'emoji_global'         => false, // If true, will force emoji on all subsites.
				'prefetch'             => array(),
				'preconnect'           => array(),
				'db_cleanups'          => false,
				'cart_fragments'       => false,
				'lazy_load'            => array(
					'enabled'   => false,
					'method'    => 'click',
					'button'    => array(
						'dimensions' => array(
							'height' => 0,
							'width'  => 0,
							'radius' => 0,
						),
						'color'      => array(
							'background' => '',
							'border'     => '',
							'hover'      => '',
						),
						'alignment'  => array(
							'align'      => 'center',
							'full_width' => 'on',
							'left'       => 0,
							'right'      => 0,
							'top'        => 0,
							'bottom'     => 0,
						),
					),
					'threshold' => 10,
				),
			),
			'rss'         => array(
				'enabled'  => true,
				'duration' => 3600,
			),
			'settings'    => array(
				'accessible_colors' => false,
				'remove_settings'   => false,
				'remove_data'       => false,
				'tracking'          => false,
				'control'           => false, // Cache control in admin bar.
			),
			'redis'       => array(
				'enabled' => false,
			),
		);

		/**
		 * Filter the default settings.
		 * Useful when adding new settings to the plugin
		 */
		return apply_filters( 'wp_hummingbird_default_options', $defaults );
	}

	/**
	 * Array of settings per sub-site.
	 *
	 * @access private
	 *
	 * @param string $module  Module for to get sub site setting fields for.
	 *
	 * @return array
	 */
	private static function get_blog_option_names( $module ) {
		if ( ! in_array( $module, self::$network_modules, true ) ) {
			return array();
		}

		$options = array(
			'caching'     => array( 'expiry_css', 'expiry_javascript', 'expiry_media', 'expiry_images' ),
			'minify'      => array( 'minify_blog', 'view', 'type', 'do_assets', 'block', 'dont_minify', 'dont_combine', 'position', 'defer', 'inline', 'nocdn', 'fonts', 'preload', 'async' ),
			'page_cache'  => array( 'cache_blog' ),
			'performance' => array( 'dismissed' ),
			'advanced'    => array( 'query_string', 'emoji', 'prefetch', 'preconnect', 'cart_fragments' ),
			'cloudflare'  => array( 'enabled', 'connected', 'last_check', 'email', 'api_key', 'account_id', 'zone', 'zone_name', 'plan', 'page_rules', 'cache_expiry', 'apo_paid', 'apo' ),
		);

		return $options[ $module ];
	}

	/**
	 * Filter out sub site options from network options on multisite.
	 *
	 * @access private
	 *
	 * @param array $options  Options array.
	 *
	 * @return array
	 */
	private static function filter_multisite_options( $options ) {
		$network_options = array();
		$blog_options    = array();

		foreach ( $options as $module => $setting ) {
			/*
			 * Skip if module is not registered.
			 * Only needed in case an update to 1.8 manually by replacing the files.
			 */
			if ( ! in_array( $module, self::$available_modules, true ) ) {
				continue;
			}

			$data = array_fill_keys( self::get_blog_option_names( $module ), self::get_blog_option_names( $module ) );

			$network_options[ $module ] = array_diff_key( $setting, $data );
			$blog_options[ $module ]    = array_intersect_key( $setting, $data );
		}

		// array_filter will remove all empty values.
		return array(
			'network' => $network_options,
			'blog'    => array_filter( $blog_options ),
		);
	}

	/**
	 * Reset database to default settings. Will overwrite all current settings.
	 * This can be moved out to update_settings, because it's almost identical.
	 */
	public static function reset_to_defaults() {
		Utils::get_module( 'redis' )->disable();

		$defaults = self::get_default_settings();

		if ( ! is_multisite() ) {
			update_option( 'wphb_settings', $defaults );
		} else {
			$options = self::filter_multisite_options( $defaults );
			update_site_option( 'wphb_settings', $options['network'] );
			update_option( 'wphb_settings', $options['blog'] );
		}
	}

	/**
	 * Return the plugin settings.
	 *
	 * @param bool|string $for_module  Module to fetch options for.
	 *
	 * @return array  Hummingbird settings.
	 */
	public static function get_settings( $for_module = false ) {
		if ( ! is_multisite() ) {
			$options = get_option( 'wphb_settings', array() );
		} else {
			$blog_options    = get_option( 'wphb_settings', array() );
			$network_options = get_site_option( 'wphb_settings', array() );
			$options         = array_merge_recursive( $blog_options, $network_options );
		}

		$defaults = self::get_default_settings();

		// We need to parse each module individually.
		foreach ( $defaults as $module => $option ) {
			// If there is nothing set in the current option, we use the default set.
			if ( ! isset( $options[ $module ] ) ) {
				$options[ $module ] = $option;
				continue;
			}
			// Else we combine defaults with current options.
			$options[ $module ] = wp_parse_args( $options[ $module ], $option );
		}

		return ( $for_module ) ? $options[ $for_module ] : $options;
	}

	/**
	 * Update the plugin settings.
	 *
	 * @param array       $new_settings  New settings.
	 * @param bool|string $for_module    Module to update settings for.
	 */
	public static function update_settings( $new_settings, $for_module = false ) {
		if ( $for_module ) {
			$options                = self::get_settings();
			$options[ $for_module ] = $new_settings;
			$new_settings           = $options;
		}

		if ( ! is_multisite() ) {
			update_option( 'wphb_settings', $new_settings );
		} else {
			$options = self::filter_multisite_options( $new_settings );
			update_site_option( 'wphb_settings', $options['network'] );
			update_option( 'wphb_settings', $options['blog'] );
		}
	}

	/**
	 * Get setting.
	 *
	 * @param string      $option_name  Return a single WP Hummingbird setting.
	 * @param bool|string $for_module   Module to fetch options for.
	 *
	 * @return mixed
	 */
	public static function get_setting( $option_name, $for_module = false ) {
		$options = self::get_settings( $for_module );

		if ( ! isset( $options[ $option_name ] ) ) {
			return '';
		}

		/**
		 * Failsafe for when options are stored incorrectly.
		 */
		$defaults = self::get_default_settings();
		if ( $for_module ) {
			$defaults = $defaults[ $for_module ];
		}

		if ( self::is_exception( $for_module, $options, $option_name ) ) {
			return $options[ $option_name ];
		}

		if ( gettype( $defaults[ $option_name ] ) !== gettype( $options[ $option_name ] ) ) {
			self::update_setting( $option_name, $defaults[ $option_name ], $for_module );
			return $defaults[ $option_name ];
		}

		return $options[ $option_name ];
	}

	/**
	 * Check if setting has an exception.
	 *
	 * In get_settings we compare the values to defaults (including value type).
	 * Two options can be bool/string: minify -> enabled and page_cache -> enabled.
	 *
	 * @since 1.8.1
	 *
	 * @param string $module       Module.
	 * @param array  $options      Options.
	 * @param string $option_name  Option name.
	 *
	 * @return bool
	 */
	private static function is_exception( $module, $options, $option_name ) {
		$exceptions = array(
			'minify'      => 'super-admins',
			'page_cache'  => 'blog-admins',
			'performance' => 'super-admins',
		);

		// Cache control in Settings can be an array or a boolean.
		if ( 'settings' === $module && 'control' === $option_name && is_array( $options[ $option_name ] ) ) {
			return true;
		}

		if ( isset( $exceptions[ $module ] ) && $exceptions[ $module ] === $options[ $option_name ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Update selected plugin setting.
	 *
	 * @param string      $option_name  Setting name.
	 * @param mixed       $value        Setting value.
	 * @param bool|string $for_module   Module to update settings for.
	 */
	public static function update_setting( $option_name, $value, $for_module = false ) {
		$options = self::get_settings( $for_module );

		$options[ $option_name ] = $value;

		self::update_settings( $options, $for_module );
	}

	/**
	 * Return a single WP Hummingbird option.
	 *
	 * @param string $option   Option.
	 * @param mixed  $default  Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed
	 */
	public static function get( $option, $default = false ) {
		if ( ! is_main_site() ) {
			$value = get_option( $option, $default );
		} else {
			$value = get_site_option( $option, $default );
		}

		return $value;
	}

	/**
	 * Delete a single WP Hummingbird option.
	 *
	 * @param string $option  Option.
	 */
	public static function delete( $option ) {
		if ( ! is_main_site() ) {
			delete_option( $option );
		} else {
			delete_site_option( $option );
		}
	}

	/**
	 * Update option.
	 *
	 * @param string      $option   WP Hummingbird option name.
	 * @param mixed       $value    WP Hummingbird option value.
	 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
	 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
	 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
	 *                              the default value is 'yes'. Default null.
	 */
	public static function update( $option, $value, $autoload = null ) {
		if ( ! is_main_site() ) {
			update_option( $option, $value, $autoload );
		} else {
			update_site_option( $option, $value );
		}
	}

}
