<?php
/**
 * WPConfig trait.
 *
 * Allows read/write of wp-config.php file.
 *
 * @since 1.7.0
 * @since 2.5.0  Improved functionality and moved to a trait from Page_Cache module.
 * @package Hummingbird\Core
 */

namespace Hummingbird\Core\Traits;

use Hummingbird\Core\Filesystem;
use Hummingbird\WP_Hummingbird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait WPConfig
 */
trait WPConfig {

	/**
	 * Config file path.
	 *
	 * @var string $wp_config_file
	 */
	public $wp_config_file = ABSPATH . 'wp-config.php';

	/**
	 * Add a define to wp-config.php file.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name   Define name.
	 * @param string $value  Define value.
	 */
	public function wpconfig_add( $name, $value ) {
		if ( ! $this->can_continue() ) {
			return;
		}

		$value = $this->prepare_value( $value );
		$lines = file( $this->wp_config_file );

		// Generate the new file data.
		$new_file = array();
		$added    = false;
		foreach ( $lines as $line ) {
			// Maybe there's already a define?
			if ( preg_match( "/define\(\s*'{$name}'/i", $line ) ) {
				$added = true;
				WP_Hummingbird::get_instance()->core->logger->log( "Added define( {$name}, {$value} ) to wp-config.php file.", $this->get_slug() );
				$new_file[] = "define( '{$name}', {$value} ); // Added by Hummingbird\n";
				continue;
			}

			// If we reach the end and no define - add it.
			if ( ! $added && preg_match( "/\/\* That's all, stop editing!.*/i", $line ) ) {
				WP_Hummingbird::get_instance()->core->logger->log( "Added define( {$name}, {$value} ) to wp-config.php file.", $this->get_slug() );
				$new_file[] = "define( '{$name}', {$value} ); // Added by Hummingbird\n";
			}

			$new_file[] = $line;
		}

		$wphb_fs = Filesystem::instance();
		$wphb_fs->write( $this->wp_config_file, implode( '', $new_file ) );
	}

	/**
	 * Remove a define from wp-config.php file.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name  Define name.
	 */
	public function wpconfig_remove( $name ) {
		if ( ! $this->can_continue() ) {
			return;
		}

		$lines = file( $this->wp_config_file );

		// Generate the new file data.
		$new_file = array();
		foreach ( $lines as $line ) {
			if ( preg_match( "/define\(\s*'{$name}'/i", $line ) ) {
				WP_Hummingbird::get_instance()->core->logger->log( "Removed define( '{$name}', ... ) from wp-config.php file.", $this->get_slug() );
				continue;
			}

			$new_file[] = $line;
		}

		$wphb_fs = Filesystem::instance();
		$wphb_fs->write( $this->wp_config_file, implode( '', $new_file ) );
	}

	/**
	 * Check if we can access the file.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	private function can_continue() {
		// Taken from wp-load.php.
		// If config file doesn't exists in root directory, try to locate it in a directory above.
		if ( ! file_exists( $this->wp_config_file )
			&& file_exists( dirname( ABSPATH ) . '/wp-config.php' )
			&& ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' )
		) {
			// The config file resides one level above ABSPATH but is not part of another installation.
			$this->wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( ! file_exists( $this->wp_config_file ) ) {
			WP_Hummingbird::get_instance()->core->logger->log( 'Failed to locate wp-config.php file.', $this->get_slug() );
			return false;
		}

		if ( ! is_writable( $this->wp_config_file ) ) {
			WP_Hummingbird::get_instance()->core->logger->log( 'Failed to open wp-config.php for writing.', $this->get_slug() );
			return false;
		}

		return true;
	}

	/**
	 * Try to convert the value to a proper string, so that it is properly written to wp-config.php file.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed $value  Value.
	 *
	 * @return string
	 */
	private function prepare_value( $value ) {
		// Make sure to enclose in single quotes if this is a string value.
		if ( is_string( $value ) ) {
			return "'{$value}'";
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return $value;
	}

}
