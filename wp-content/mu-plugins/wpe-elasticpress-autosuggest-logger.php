<?php
/**
 * Plugin Name: WPE ElasticPress Autosuggest Logger
 * Plugin URI: https://wpengine.com
 * Description: Appends search terms to the url when elasticpress autosuggest is enabled
 * Version: 1.0.0
 * Text Domain: wpe-elasticpress-autosuggest-logger
 * Domain Path: /languages
 * Author: WP Engine
 * Author URI: https://wpengine.com/
 * License: GPLv2
 *
 * @package WPEngine\wpe_elasticpress_autosuggest_logger
 */

define( 'WPENGINE_WPE_ES_AUTOSUGGEST_LOGGER_VERSION', '1.0.0' );
add_action( 'plugins_loaded', 'wpe_elasticpress_autosuggest_logger_loader' );

/**
 * Registers all hooks necessary for the feature
 *
 * @since 1.0.0
 */
function wpe_elasticpress_autosuggest_logger_loader() {
	// Check if this plugin has been disabled in the config file
	if ( defined( 'DISABLE_WPE_ES_AUTOSUGGEST_LOGGER' ) && true === DISABLE_WPE_ES_AUTOSUGGEST_LOGGER ) {

		return;

	} else {

		// Get active plugins
		$active_plugins = get_option( 'active_plugins' );
		// Check if get_option returns an array
		if ( is_array( $active_plugins ) ) {

			// Check if elasticpress plugin is activated
			if ( in_array( 'elasticpress/elasticpress.php', $active_plugins, true ) ) {

				// Enable autosuggest metrics script
				add_action( 'wp_enqueue_scripts', 'wpe_elasticpress_autosuggest_logger_action_wp_enqueue_scripts' );

			}
		}
	}
}

/**
 * Action wp_enqueue_scripts
 *
 * Enqueue's necessary JavaScript required for processing
 *
 * @since 1.0.0
 */
function wpe_elasticpress_autosuggest_logger_action_wp_enqueue_scripts() {

	// Get elasticpress feature settings
	$ep_settings = get_option( 'ep_feature_settings' );
	// If autosuggest is enabled register the autosuggest metrics script
	if ( is_array( $ep_settings ) && isset( $ep_settings['autosuggest'] ) && isset( $ep_settings['autosuggest']['active'] ) && true === $ep_settings['autosuggest']['active'] ) {

		$min        = '-min';
		$version    = WPENGINE_WPE_ES_AUTOSUGGEST_LOGGER_VERSION;
		$plugin_url = plugin_dir_url( __FILE__ );

		if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {

			$min     = '';
			$version = time();

		}

		wp_register_script( 'wpe-autosuggest-metrics', $plugin_url . 'wpe-elasticpress-autosuggest-logger/wpe-autosuggest' . $min . '.js', array( 'elasticpress-autosuggest' ), $version, true );

		wp_enqueue_script( 'wpe-autosuggest-metrics' );

	}
}
