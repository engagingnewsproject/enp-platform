<?php
/**
 * Handles the functionality to hide error reporting in a WordPress environment.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

use WP_Error;
use WP_Defender\Component\Security_Tweaks\Servers\Server;
use WP_Defender\Component\Security_Tweak as Security_Tweak_Component;

/**
 * It provides methods to check, enable, disable, and revert the error reporting settings.
 */
class Hide_Error extends Abstract_Security_Tweaks {

	/**
	 * Slug identifier for the component.
	 *
	 * @var string
	 */
	public string $slug = 'hide-error';

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check(): bool {
		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		return ! $debug;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|WP_Error
	 */
	public function process() {
		return $this->set_debug_data( false );
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool|WP_Error
	 */
	public function revert() {
		return $this->set_debug_data( true );
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up(): bool {
		return true;
	}

	/**
	 * Set debug data in wp-config.php.
	 *
	 * @param  bool $value  of constant.
	 *
	 * @return bool|WP_Error
	 */
	private function set_debug_data( bool $value ) {
		$sec_tweak_component = new Security_Tweak_Component();
		$obj_file            = 'flywheel' === Server::get_current_server()
			? $sec_tweak_component->advanced_check_file()
			: $sec_tweak_component->file();
		if ( false === $obj_file ) {
			return new WP_Error(
				'defender_file_not_writable',
				esc_html__( 'The file wp-config.php is not writable', 'wpdef' )
			);
		} elseif ( is_numeric( $obj_file ) ) {
			return new WP_Error(
				'defender_file_not_writable',
				$sec_tweak_component->show_hosting_notice( 'debug mode' )
			);
		}

		$value             = $value ? 'true' : 'false';
		$pattern           = "/^define\(\s*['|\"]WP_DEBUG['|\"],(.*)\)/";
		$debug_type        = 'WP_DEBUG';
		$hook_line_pattern = $sec_tweak_component->get_hook_line_pattern();
		$debug_line        = "define( '{$debug_type}', {$value} ); // Added by Defender";
		$lines             = array();
		$line_found        = false;
		$hook_line_no      = null;

		foreach ( $obj_file as $line ) {
			if ( ! $line_found && preg_match( $pattern, $line ) ) {
				// If this is revert request and the changes is not made by us throw error.
				if ( 'true' === $value && ! preg_match(
					"/^define\(\s*['|\"]{$debug_type}['|\"],(.*)\);\s*\/\/\s*Added\s*by\s*Defender.?.*/i",
					$line
				) ) {
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
				esc_html__( 'Error writing to file.', 'wpdef' ),
				404
			);
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Hide error reporting', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		return esc_html__( 'Error debugging is currently allowed.', 'wpdef' );
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'slug'             => $this->slug,
			'title'            => $this->get_label(),
			'errorReason'      => $this->get_error_reason(),
			'successReason'    => esc_html__(
				'You\'ve disabled all error reporting, Houston will never report a problem.',
				'wpdef'
			),
			'misc'             => array(),
			'bulk_description' => esc_html__(
				'Error debugging feature is useful for active development, but on live sites provides hackers yet another way to find loopholes in your site\'s security. We will disable error reporting for you.',
				'wpdef'
			),
			'bulk_title'       => esc_html__( 'Error Reporting', 'wpdef' ),
		);
	}
}