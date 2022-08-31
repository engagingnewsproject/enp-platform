<?php
/**
 * Wp_Cli_Enabled_Abstraction
 *
 * @package wpengine/common-mu-plugin
 */

namespace wpe\plugin;

/**
 * Class Wp_Cli_Enabled_Abstraction
 */
class Wp_Cli_Enabled_Abstraction implements Wp_Cli_Abstraction {
	/**
	 * WP CLI's log
	 *
	 * Documentation: https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-log/
	 *
	 * @param string $message The log message.
	 * @return void
	 */
	public function log( $message ) {
		\WP_CLI::log( $message );
	}

	/**
	 * WP CLI's error
	 *
	 * Documentation: https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-error/
	 *
	 * @param string $message The error message.
	 * @return void
	 */
	public function error( $message ) {
		\WP_CLI::error( $message );
	}

	/**
	 * WP CLI's add_hook
	 *
	 * Documentation: https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-add-hook/
	 *
	 * @param string       $hook_name Name of the action to be hooked.
	 * @param string|array $callback Either a string or an array containing the function/method to be called.
	 * @return void
	 */
	public function add_hook( $hook_name, $callback ) {
		\WP_CLI::add_hook( $hook_name, $callback );
	}
}
