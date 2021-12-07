<?php

namespace WP_Defender\Component\Security_Tweaks;

use WP_Defender\Component\Security_Tweak as Security_Tweak_Component;
use WP_Defender\Component\Security_Tweaks\Servers\Server;
use WP_Error;
use Calotes\Base\Component;

/**
 * Class Hide_Error
 * @package WP_Defender\Component\Security_Tweaks
 */
class Hide_Error extends Component {
	public $slug           = 'hide-error';
	public $what_to_change = array();

	/**
	 * @return bool
	 */
	public function check() {
		$data = $this->what_to_change();

		return $data['resolved'];
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		$data                 = $this->what_to_change();
		$this->what_to_change = $data['required_change'];
		if ( empty( $this->what_to_change ) ) {
			return false;
		}

		if ( in_array( 'wp_debug', $this->what_to_change, true ) ) {
			return $this->disable_debug();
		} elseif ( in_array( 'wp_debug_display', $this->what_to_change, true ) ) {
			return $this->disable_debug_display();
		} elseif ( in_array( 'wp_debug_log', $this->what_to_change, true ) ) {
			return $this->disable_debug_log();
		}

		return false;
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		$data                 = $this->what_to_change();
		$this->what_to_change = $data['required_change'];
		if ( empty( $this->what_to_change ) ) {
			return false;
		}

		if ( in_array( 'wp_debug', $this->what_to_change, true ) ) {
			return $this->enable_debug();
		} elseif ( in_array( 'wp_debug_display', $this->what_to_change, true ) ) {
			return $this->enable_debug_display();
		} elseif ( in_array( 'wp_debug_log', $this->what_to_change, true ) ) {
			return $this->enable_debug_log();
		}

		return false;
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Get whether to change WP_DEBUG or WP_DEBUG_DISPLAY constant.
	 * https://wordpress.org/support/article/debugging-in-wordpress/
	 *
	 * @return array
	 */
	private function what_to_change() {
		$debug          = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_log      = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$debug_display  = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$what_to_change = array(
			'wp_debug',
			'wp_debug_log',
			'wp_debug_display',
		);
		$resolved       = false;
		if ( $debug ) {
			if ( $debug_log && $debug_display ) {
				return array(
					'resolved'        => $resolved,
					'required_change' => $what_to_change,
				);
			} elseif ( ! $debug_log && ! $debug_display ) {
				$what_to_change = array( 'wp_debug' );
			} elseif ( $debug_log && ! $debug_display ) {
				$what_to_change = array( 'wp_debug', 'wp_debug_log' );
			} elseif ( ! $debug_log && $debug_display ) {
				$what_to_change = array( 'wp_debug', 'wp_debug_display' );
			}
		} else {
			if ( ! $debug_log && ! $debug_display ) {
				return array(
					'resolved'        => true,
					'required_change' => $what_to_change,
				);
			} elseif ( $debug_log && $debug_display ) {
				$what_to_change = array( 'wp_debug' );
			} elseif ( ! $debug_log && $debug_display ) {
				$what_to_change = array( 'wp_debug', 'wp_debug_log' );
			} elseif ( $debug_log && ! $debug_display ) {
				$what_to_change = array( 'wp_debug', 'wp_debug_display' );
			}
			$resolved = true;
		}

		return array(
			'resolved'        => $resolved,
			'required_change' => $what_to_change,
		);
	}

	/**
	 * Enable debugging.
	 *
	 * @return bool
	 */
	private function enable_debug() {
		return $this->set_debug_data( 'wp_debug', true );
	}

	/**
	 * Disable debugging.
	 *
	 * @return bool
	 */
	private function disable_debug() {
		return $this->set_debug_data( 'wp_debug', false );
	}

	/**
	 * Enable debug display.
	 *
	 * @return bool
	 */
	private function enable_debug_display() {
		return $this->set_debug_data( 'wp_debug_display', true );
	}

	/**
	 * Disable debug display.
	 *
	 * @return bool
	 */
	private function disable_debug_display() {
		return $this->set_debug_data( 'wp_debug_display', false );
	}

	/**
	 * Enable debug log.
	 *
	 * @return bool
	 */
	private function enable_debug_log() {
		return $this->set_debug_data( 'wp_debug_log', true );
	}

	/**
	 * Disable debug log.
	 *
	 * @return bool
	 */
	private function disable_debug_log() {
		return $this->set_debug_data( 'wp_debug_log', false );
	}

	/**
	 * Set debug data in wp-config.php.
	 *
	 * @param string $debug_type
	 * @param bool   $value
	 *
	 * @return bool|WP_Error
	 */
	private function set_debug_data( $debug_type, $value ) {
		$sec_tweak_component = new Security_Tweak_Component();
		$obj_file            = 'flywheel' === Server::get_current_server()
			? $sec_tweak_component->advanced_check_file()
			: $sec_tweak_component->file();
		if ( false === $obj_file ) {
			return new WP_Error(
				'defender_file_not_writable',
				__( 'The file wp-config.php is not writable', 'wpdef' )
			);
		} elseif ( is_numeric( $obj_file ) ) {
			return new WP_Error(
				'defender_file_not_writable',
				$sec_tweak_component->show_hosting_notice( 'debug mode' )
			);
		}

		$value             = $value ? 'true' : 'false';
		$pattern           = $this->get_pattern( $debug_type );
		$debug_type        = strtoupper( $debug_type );
		$hook_line_pattern = $sec_tweak_component->get_hook_line_pattern();
		$debug_line        = "define( '{$debug_type}', {$value} ); // Added by Defender";
		$lines             = array();
		$line_found        = false;
		$hook_line_no      = null;

		foreach ( $obj_file as $line ) {
			if ( ! $line_found && preg_match( $pattern, $line ) ) {
				// If this is revert request and the changes is not made by us throw error.
				if ( 'true' === $value && ! preg_match( "/^define\(\s*['|\"]{$debug_type}['|\"],(.*)\);\s*\/\/\s*Added\s*by\s*Defender.?.*/i", $line ) ) {
					return new WP_Error(
						'defender_file_not_writable',
						$sec_tweak_component->show_hosting_notice_with_code( $debug_type, $debug_line )
					);
				}

				$lines[]    = $debug_line;
				$line_found = true;
				continue;
			}

			// If there is no match, keep reference of `hook_line_no` so that we can insert data there as needed.
			if ( ! $line_found && preg_match( $hook_line_pattern, $line ) ) {
				$hook_line_no               = $obj_file->key();
				$lines[ $hook_line_no + 1 ] = trim( $line );
				continue;
			}

			$lines[] = trim( $line );
		}

		// There is no match, so set WP_DEBUG data just before the hook line ei: `$table_prefix`.
		if ( ! $line_found && ! is_null( $hook_line_no ) ) {
			$line_found             = true;
			$lines[ $hook_line_no ] = $debug_line;
			ksort( $lines );
		}

		return $line_found
			? $sec_tweak_component->write( $lines )
			: new WP_Error(
				'defender_line_not_found',
				__( 'Error writing to file.', 'wpdef' ),
				404
			);
	}

	/**
	 * Get pattern for any WP_DEBUG const.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function get_pattern( $type ) {
		if ( 'wp_debug' === $type ) {
			return $this->get_wp_debug_pattern();
		} elseif ( 'wp_debug_display' === $type ) {
			return $this->get_wp_debug_display_pattern();
		} else {
			return $this->get_wp_debug_log_pattern();
		}
	}

	/**
	 * Get pattern for WP_DEBUG.
	 *
	 * @return string
	 */
	private function get_wp_debug_pattern() {
		return "/^define\(\s*['|\"]WP_DEBUG['|\"],(.*)\)/";
	}

	/**
	 * Get pattern for WP_DEBUG_DISPLAY.
	 *
	 * @return string
	 */
	private function get_wp_debug_display_pattern() {
		return "/^define\(\s*['|\"]WP_DEBUG_DISPLAY['|\"], (.*)\)/";
	}

	/**
	 * Get pattern for WP_DEBUG_LOG.
	 *
	 * @return string
	 */
	private function get_wp_debug_log_pattern() {
		return "/^define\(\s*['|\"]WP_DEBUG_LOG['|\"], (.*)\)/";
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'slug'             => $this->slug,
			'title'            => __( 'Hide error reporting', 'wpdef' ),
			'errorReason'      => __( 'Error debugging is currently allowed.', 'wpdef' ),
			'successReason'    => __( 'You\'ve disabled all error reporting, Houston will never report a problem.', 'wpdef' ),
			'misc'             => array(),
			'bulk_description' => __( 'Error debugging feature is useful for active development, but on live sites provides hackers yet another way to find loopholes in your site\'s security. We will disable error reporting for you.', 'wpdef' ),
			'bulk_title'       => __( 'Error Reporting', 'wpdef' ),
		);
	}
}