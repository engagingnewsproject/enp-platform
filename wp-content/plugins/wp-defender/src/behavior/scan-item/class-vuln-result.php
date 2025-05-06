<?php
/**
 * Represents a vulnerability result.
 *
 * @package WP_Defender\Behavior\Scan_Item
 */

namespace WP_Defender\Behavior\Scan_Item;

use WP_Error;
use Theme_Upgrader;
use Plugin_Upgrader;
use WP_Defender\Traits\IO;
use WP_Defender\Model\Scan;
use WP_Defender\Traits\Theme;
use WP_Defender\Traits\Plugin;
use Calotes\Component\Behavior;
use WP_Defender\Traits\Formats;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Component\Error_Code;
use WP_Defender\Component\Security_Tweaks\WP_Version;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * Represents a vulnerability result.
 */
class Vuln_Result extends Behavior {

	use Formats;
	use IO;
	use Plugin;
	use Theme;

	/**
	 * Checks if upgrade is possible based on the presence of fixed version in vulnerabilities.
	 *
	 * @param  array $bugs  The array of vulnerabilities to check.
	 *
	 * @return string The upgrade status ('disabled' or 'enabled').
	 */
	protected function upgrade_possible( array $bugs ): string {
		$upgrade = 'disabled';
		foreach ( $bugs as $bug ) {
			if ( ! empty( $bug['fixed_in'] ) ) {
				$upgrade = 'enabled';
				break;
			}
		}

		return $upgrade;
	}

	/**
	 * Converts the scan item information into an array for output.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$data = $this->owner->raw_data;
		if ( isset( $data['name'], $data['version'], $data['bugs'] ) ) {
			if ( 'wp_core' === $data['type'] ) {
				// Check if the current WP version is the latest.
				$upgrade = ( new WP_Version() )->check() ? 'disabled' : 'enabled';
			} elseif ( in_array( $data['type'], array( 'plugin', 'theme' ), true ) ) {
				$upgrade = $this->upgrade_possible( $data['bugs'] );
			} else {
				$upgrade = 'disabled';
			}

			return array(
				'id'            => $this->owner->id,
				'type'          => Scan_Item::TYPE_VULNERABILITY,
				'file_name'     => $data['name'],
				'short_desc'    => sprintf(
				/* translators: %s: Version of WP core, plugin or theme. */
					esc_html__( 'Vulnerability found in %s.', 'wpdef' ),
					$data['version']
				),
				'details'       => isset( $data['new_structure'] )
					? $this->get_details_as_array( $data )
					: $this->get_detail_as_string( $data ),
				// Need for all scan items for WP-CLI command. Full path = base slug for this item.
				'full_path'     => $data['slug'],
				'new_structure' => isset( $data['new_structure'] ) ? 'yes' : 'no',
				'upgrade'       => $upgrade,
			);
		}

		return array();
	}

	/**
	 * Ignore the suspicious file for the current scan.
	 *
	 * @return array An array with a message indicating successful ignore.
	 */
	public function ignore(): array {
		$scan = Scan::get_last();
		$scan->ignore_issue( $this->owner->id );

		return array(
			'message' => esc_html__( 'The suspicious file has been successfully ignored.', 'wpdef' ),
		);
	}

	/**
	 * Allow the suspicious file for the current scan.
	 *
	 * @return array An array with a message indicating successful restoration.
	 */
	public function unignore(): array {
		$scan = Scan::get_last();
		$scan->unignore_issue( $this->owner->id );

		return array(
			'message' => esc_html__( 'The suspicious file has been successfully restored.', 'wpdef' ),
		);
	}

	/**
	 * Resolves the vulnerability issue based on the type of vulnerability.
	 *
	 * @return array|bool|WP_Error The resolved issue or an error if the issue type is not found.
	 */
	public function resolve() {
		$data = $this->owner->raw_data;
		// Redirect for WordPress-type.
		if ( 'wp_core' === $data['type'] ) {
			return array( 'url' => network_admin_url( 'update-core.php' ) );
		} elseif ( 'plugin' === $data['type'] ) {
			return $this->upgrade_plugin( $data['slug'] );
		} elseif ( 'theme' === $data['type'] ) {
			return $this->upgrade_theme( $data['base_slug'] );
		}

		// If type does not match.
		return new WP_Error(
			Error_Code::INVALID,
			esc_html__( 'Please try again! We could not find the issue type.', 'wpdef' )
		);
	}

	/**
	 * Upgrades the theme based on the provided slug.
	 *
	 * @param  mixed $slug  The slug of the theme to upgrade.
	 *
	 * @return array|WP_Error An array with a message if the upgrade is successful or a WP_Error on failure.
	 */
	private function upgrade_theme( $slug ) {
		$skin     = new Silent_Skin();
		$upgrader = new Theme_Upgrader( $skin );
		$ret      = $upgrader->upgrade( $slug );

		if ( true === $ret ) {
			$model = Scan::get_last();
			$model->remove_issue( $this->owner->id );

			do_action( 'wpdef_fixed_scan_issue', 'vulnerability', 'resolve' );

			return array( 'message' => esc_html__( 'This item has been resolved.', 'wpdef' ) );
		}

		// This is WP error.
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}

		// Sometimes it returns false because of it could not complete the update process.
		return new WP_Error(
			Error_Code::INVALID,
			esc_html__( "We couldn't update your theme. Please try updating with another method.", 'wpdef' )
		);
	}

	/**
	 * Upgrade a plugin based on the provided slug.
	 *
	 * @param  mixed $slug  The slug of the plugin to upgrade.
	 *
	 * @return array An array with information about the upgrade status.
	 * @since 2.8.1
	 */
	private function upgrade_plugin( $slug ): array {
		$skin     = new Plugin_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->bulk_upgrade( array( $slug ) );

		if ( is_wp_error( $skin->result ) ) {
			return array(
				'type_notice' => 'error',
				'message'     => $skin->result->get_error_message(),
			);
		} elseif ( $skin->get_errors()->has_errors() ) {
			return array(
				'type_notice' => 'error',
				'message'     => $skin->get_error_messages(),
			);
		} elseif ( is_array( $result ) && ! empty( $result[ $slug ] ) ) {
			$model = Scan::get_last();
			$model->remove_issue( $this->owner->id );

			do_action( 'wpdef_fixed_scan_issue', 'vulnerability', 'resolve' );

			return array( 'message' => esc_html__( 'This item has been resolved.', 'wpdef' ) );
		} elseif ( false === $result ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__(
					'Unable to connect to the filesystem. Please confirm your credentials.',
					'wpdef'
				),
			);
		}

		return array(
			'type_notice' => 'info',
			'message'     => esc_html__( 'There is no update available for this plugin.', 'wpdef' ),
		);
	}

	/**
	 * Removes a vulnerability at the specified path.
	 *
	 * @param  string $path  The path of the vulnerability to remove.
	 *
	 * @return bool Returns true on successful removal, false otherwise.
	 */
	private function remove_vulnerability( string $path ): bool {
		if ( is_dir( $path ) ) {
			return $this->delete_dir( $path );
		} else {
			// Sometimes a plugin consists of one file.
			wp_delete_file( $path );
			return true;
		}
	}

	/**
	 * Removes a plugin based on the provided data, checking if it is active and has the necessary permissions.
	 *
	 * @param  array $data  An array containing information about the plugin to be removed.
	 *
	 * @return array An array with the status of the removal process and any related messages.
	 */
	private function remove_plugin( array $data ): array {
		$active = $this->is_active_plugin( $data['slug'] );
		if ( $active ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__( 'This plugin cannot be removed because it is active.', 'wpdef' ),
			);
		}

		$abs_path = wp_normalize_path( WP_PLUGIN_DIR ) . DIRECTORY_SEPARATOR . $data['base_slug'];
		if ( file_exists( $abs_path ) && ! $this->remove_vulnerability( $abs_path ) ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__(
					'Defender does not have enough permission to remove this plugin.',
					'wpdef'
				),
			);
		}

		$message = sprintf(
		/* translators: %s: Plugin name. */
			esc_html__( '%s plugin', 'wpdef' ),
			'<b>' . $data['name'] . '</b>'
		);
		$this->log( $message . 'is deleted', Scan_Controller::SCAN_LOG );
		$model = Scan::get_last();
		$model->remove_issue( $this->owner->id );

		do_action( 'wpdef_fixed_scan_issue', 'vulnerability', 'delete' );

		return array(
			'collect_type' => true,
			'message'      => $message,
		);
	}

	/**
	 * Removes a theme from the system.
	 *
	 * @param  array $data  The data of the theme to be removed.
	 *
	 * @return array The result of the removal operation.
	 */
	private function remove_theme( array $data ): array {
		$active = $this->is_active_theme( $data['base_slug'] );
		if ( $active ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__( 'This theme cannot be removed because it is active.', 'wpdef' ),
			);
		}

		$abs_path = $this->get_path_of_themes_dir() . $data['base_slug'];
		if ( file_exists( $abs_path ) && ! $this->remove_vulnerability( $abs_path ) ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__(
					'Defender does not have enough permission to remove this theme.',
					'wpdef'
				),
			);
		}

		$message = sprintf(
		/* translators: %s: Plugin theme. */
			esc_html__( '%s theme', 'wpdef' ),
			'<b>' . $data['name'] . '</b>'
		);
		$this->log( $message . 'is deleted', Scan_Controller::SCAN_LOG );
		$model = Scan::get_last();
		$model->remove_issue( $this->owner->id );

		do_action( 'wpdef_fixed_scan_issue', 'vulnerability', 'delete' );

		return array(
			'collect_type' => true,
			'message'      => $message,
		);
	}

	/**
	 * Retrieves the vulnerability body text based on the provided bug data.
	 *
	 * @param  array $bug  An array containing information about the vulnerability.
	 *
	 * @return string The vulnerability body text.
	 */
	protected function get_vulnerability_body( array $bug ): string {
		$text  = '#' . $bug['title'] . PHP_EOL;
		$text .= '-' . esc_html__( 'Vulnerability type:', 'wpdef' ) . ' ' . $bug['vuln_type'] . PHP_EOL;
		if ( empty( $bug['fixed_in'] ) ) {
			$text .= '-' . esc_html__( 'No Update Available', 'wpdef' ) . PHP_EOL;
		} else {
			$text .= '-' . esc_html__(
				'This bug has been fixed in version:',
				'wpdef'
			) . ' ' . $bug['fixed_in'] . PHP_EOL;
		}

		return $text;
	}

	/**
	 * Returns the vulnerability details as a string.
	 *
	 * @param  array $data  The data containing the vulnerability details.
	 *
	 * @return string The vulnerability details as a string.
	 */
	public function get_detail_as_string( array $data ): string {
		$strings = array();
		foreach ( $data['bugs'] as $bug ) {
			$strings[] = $this->get_vulnerability_body( $bug );
		}

		return implode( PHP_EOL, $strings );
	}

	/**
	 * Returns the details of vulnerabilities as an array.
	 *
	 * @param  array $data  The data containing information about vulnerabilities.
	 *
	 * @return array An array with vulnerability details including score and detailed description.
	 */
	public function get_details_as_array( array $data ): array {
		$arr = array();
		foreach ( $data['bugs'] as $bug ) {
			$text  = $this->get_vulnerability_body( $bug );
			$arr[] = array(
				'score'  => $bug['cvss_score'],
				'detail' => str_replace( PHP_EOL, '<br/>', $text ),
			);
		}

		return $arr;
	}

	/**
	 * Delete inactive plugin or theme.
	 *
	 * @return array|WP_Error
	 */
	public function delete() {
		$data = $this->owner->raw_data;
		// WP core, plugin or theme.
		if ( 'wp_core' === $data['type'] ) {
			return array(
				'type_notice' => 'error',
				'message'     => esc_html__( 'WordPress core cannot be removed.', 'wpdef' ),
			);
		} elseif ( 'plugin' === $data['type'] ) {
			return $this->remove_plugin( $data );
		} elseif ( 'theme' === $data['type'] ) {
			return $this->remove_theme( $data );
		}

		// Sometimes it returns false because of it could not complete the remove process.
		return new WP_Error(
			Error_Code::INVALID,
			esc_html__( "We couldn't remove this item.", 'wpdef' )
		);
	}
}