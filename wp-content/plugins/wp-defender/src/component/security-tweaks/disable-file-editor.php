<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;
use WP_Error;
use WP_Defender\Component\Security_Tweaks\Servers\Server;
use WP_Defender\Component\Security_Tweak as Security_Tweak_Component;

/**
 * Class Disable_File_Editor
 * @package WP_Defender\Component\Security_Tweaks
 */
class Disable_File_Editor extends Component {
	public $slug = 'disable-file-editor';

	/**
	 * @return bool
	 */
	public function check() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && true === constant( 'DISALLOW_FILE_EDIT' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get pattern for DISALLOW_FILE_EDIT.
	 *
	 * @return string
	 */
	private function get_file_edit_pattern() {
		return "/^define\(\s*['|\"]DISALLOW_FILE_EDIT['|\"],(.*)\)/";
	}

	/**
	 * Set data in wp-config.php.
	 *
	 * @param bool $value
	 *
	 * @return bool|\WP_Error
	 */
	private function set_file_edit_data( $value ) {
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
				$sec_tweak_component->show_hosting_notice( 'DISALLOW_FILE_EDIT' )
			);
		}

		$file_edit         = 'DISALLOW_FILE_EDIT';
		$value             = $value ? 'true' : 'false';
		$pattern           = $this->get_file_edit_pattern();
		$hook_line_pattern = $sec_tweak_component->get_hook_line_pattern();
		$file_edit_line    = "define( '{$file_edit}', {$value} ); // Added by Defender";
		$lines             = array();
		$line_found        = false;
		$hook_line_no      = null;

		foreach ( $obj_file as $line ) {
			if ( ! $line_found && preg_match( $pattern, $line ) ) {
				// If this is revert request and the changes is not made by us throw error.
				if ( 'true' === $value && ! preg_match( "/^define\(\s*['|\"]{$file_edit}['|\"],(.*)\);\s*\/\/\s*Added\s*by\s*Defender.?.*/i", $line ) ) {
					return new WP_Error(
						'defender_file_not_writable',
						$sec_tweak_component->show_hosting_notice_with_code( $file_edit, $file_edit_line )
					);
				}

				$lines[]    = $file_edit_line;
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

		// There is no match, so set DISALLOW_FILE_EDIT data just before the hook line ei: `$table_prefix`.
		if ( ! $line_found && ! is_null( $hook_line_no ) ) {
			$line_found             = true;
			$lines[ $hook_line_no ] = $file_edit_line;
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
	 * Enable file editor.
	 *
	 * @return bool|\WP_Error
	 */
	private function enable_file_editor() {
		return $this->set_file_edit_data( false );
	}

	/**
	 * Disable file editor.
	 *
	 * @return bool|\WP_Error
	 */
	private function disable_file_editor() {
		return $this->set_file_edit_data( true );
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		return $this->disable_file_editor();
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		return $this->enable_file_editor();
	}

	/**
	 * Define the DISALLOW_FILE_EDIT constant, so we can hide the editor page.
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'slug'             => $this->slug,
			'title'            => __( 'Disable the file editor', 'wpdef' ),
			'errorReason'      => __( 'The file editor is currently enabled.', 'wpdef' ),
			'successReason'    => __( 'You\'ve disabled the file editor, winning.', 'wpdef' ),
			'misc'             => array(),
			'bulk_description' => __( 'The file editor is currently active, this means anyone with access to your login information can further edit your plugin and theme files and inject malicious code. We will disable file editor for you.', 'wpdef' ),
			'bulk_title'       => __( 'File Editor', 'wpdef' ),
		);
	}
}