<?php
/**
 * Abilities API Integration
 *
 * Handles the integration of WordPress Abilities API with Ninja Forms.
 * This class follows the Ninja Forms architecture pattern by loading as
 * part of the main plugin instance.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_Abilities_Integration
 *
 * Manages the Abilities API integration lifecycle.
 */
class NF_Abilities_Integration {

	/**
	 * @var NF_Abilities_Integration
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * Sets up hooks for Abilities API integration.
	 *
	 * @since 3.13.0
	 */
	public function __construct() {
		// Hook into plugins_loaded to initialize Abilities API
		add_action( 'plugins_loaded', array( $this, 'load_abilities_api' ), 100 );

		// Initialize MCP adapter after abilities are registered
		add_action( 'mcp_adapter_init', array( $this, 'init_mcp_adapter' ), 20 );

		// Add Ninja Forms abilities to default MCP server
		add_filter( 'mcp_adapter_default_server_config', array( $this, 'add_abilities_to_mcp_server' ), 10 );
	}

	/**
	 * Load Abilities API integration files
	 *
	 * Registers ability categories and abilities.
	 * This runs on plugins_loaded with priority 100 to ensure the Abilities API
	 * is available.
	 *
	 * @since 3.13.0
	 * @return void
	 */
	public function load_abilities_api() {
		// Check if Abilities API is available (WordPress 6.9+)
		if ( ! function_exists( 'wp_register_ability_category' ) && ! function_exists( 'wp_register_ability' ) ) {
			return; // Abilities API not available
		}

		// Load category registration
		$categories_file = Ninja_Forms::$dir . 'includes/Abilities/Categories.php';
		if ( file_exists( $categories_file ) ) {
			require_once $categories_file;
		}

		// Load ability registration
		$abilities_file = Ninja_Forms::$dir . 'includes/Abilities/Abilities.php';
		if ( file_exists( $abilities_file ) ) {
			require_once $abilities_file;
		}
	}

	/**
	 * Initialize MCP Adapter
	 *
	 * Creates MCP servers after abilities are registered.
	 * This runs after wp_abilities_api_init hook has fired.
	 *
	 * @since 3.13.0
	 * @return void
	 */
	public function init_mcp_adapter() {
		// Check if MCP Adapter is available
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return;
		}

		// MCP adapter initialization is handled by the core WordPress integration
		// Ninja Forms abilities are automatically discovered and added via the
		// add_abilities_to_mcp_server filter method
	}

	/**
	 * Add Ninja Forms abilities to the default MCP server's tools
	 *
	 * Dynamically discovers all registered Ninja Forms abilities and adds them to the MCP server.
	 * This ensures that all abilities are available as MCP tools without manual updates.
	 *
	 * @since 3.13.0
	 * @param array $config The default server configuration
	 * @return array Modified configuration
	 */
	public function add_abilities_to_mcp_server( $config ) {
		// Ensure tools array exists
		if ( ! isset( $config['tools'] ) || ! is_array( $config['tools'] ) ) {
			$config['tools'] = array();
		}

		// Get all registered abilities
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return $config;
		}

		$all_abilities = wp_get_abilities();

		// Filter for Ninja Forms abilities (those starting with 'ninjaforms/')
		$ninja_forms_abilities = array();
		foreach ( $all_abilities as $ability_name => $ability_config ) {
			if ( strpos( $ability_name, 'ninjaforms/' ) === 0 ) {
				// Only add abilities that have MCP metadata configured
				// Note: $ability_config is a WP_Ability object, not an array
				$meta = $ability_config->get_meta();
				if ( isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true ) {
					$ninja_forms_abilities[] = $ability_name;
				}
			}
		}

		// Add discovered Ninja Forms abilities to the tools array
		$config['tools'] = array_merge(
			$config['tools'],
			$ninja_forms_abilities
		);

		return $config;
	}

	/**
	 * Get instance
	 *
	 * @since 3.13.0
	 * @return NF_Abilities_Integration
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof NF_Abilities_Integration ) ) {
			self::$instance = new NF_Abilities_Integration();
		}
		return self::$instance;
	}
}
