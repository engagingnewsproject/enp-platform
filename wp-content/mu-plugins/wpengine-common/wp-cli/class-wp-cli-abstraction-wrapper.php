<?php
/**
 * Wp_Cli_Abstraction_Wrapper
 *
 * @package wpengine/common-mu-plugin
 */

namespace wpe\plugin;

/**
 * Class Wp_Cli_Abstraction_Wrapper
 *
 * This class abstracts away the need to check for the presence of WP CLI.
 */
class Wp_Cli_Abstraction_Wrapper {
	/**
	 * Create an appropriate abstraction based on whether WP CLI is available
	 *
	 * @return Wp_Cli_Disabled_Abstraction|Wp_Cli_Enabled_Abstraction
	 */
	public static function create_abstraction() {
		return ( defined( 'WP_CLI' ) && WP_CLI ) ? new Wp_Cli_Enabled_Abstraction() : new Wp_Cli_Disabled_Abstraction();
	}
}
