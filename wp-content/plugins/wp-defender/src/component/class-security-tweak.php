<?php
/**
 * Handles security tweaks.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Exception;
use SplFileObject;
use Calotes\Base\Component;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Setting\Security_Tweaks;

/**
 * Handles security tweaks operations such as retrieving issues, ignored, and fixed tweaks.
 */
class Security_Tweak extends Component {

	public const LOG_FILE_NAME = 'recommendations.log';

	/**
	 * Model instance for caching.
	 *
	 * @var Security_Tweaks
	 */
	public $model;

	/**
	 * Retrieves the model instance, creating it if it does not exist.
	 *
	 * @return Security_Tweaks The security tweaks model.
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}
		$this->model = new Security_Tweaks();

		return $this->model;
	}

	/**
	 * Retrieves a list of current issues from the cache.
	 *
	 * @return array List of issues with labels and URLs.
	 */
	public function get_issues() {
		$issues       = array();
		$tweaks       = Array_Cache::get( 'tweaks', 'tweaks' );
		$issue_tweaks = $this->get_model()->issues;
		foreach ( $issue_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$issues[] = array(
					'label' => $tweaks[ $slug ]->get_label(),
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' ) . '#' . $slug,
				);
			}
		}

		return $issues;
	}

	/**
	 * Retrieves a list of ignored tweaks from the cache.
	 *
	 * @return array List of ignored tweaks with labels and URLs.
	 */
	public function get_ignored() {
		$ignored        = array();
		$tweaks         = Array_Cache::get( 'tweaks', 'tweaks' );
		$ignored_tweaks = $this->get_model()->ignore;
		foreach ( $ignored_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$ignored[] = array(
					'label' => $tweaks[ $slug ]->get_label(),
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=ignored' ) . '#' . $slug,
				);
			}
		}

		return $ignored;
	}

	/**
	 * Retrieves a list of fixed tweaks from the cache.
	 *
	 * @return array List of fixed tweaks with labels and URLs.
	 */
	public function get_fixed() {
		$fixed        = array();
		$tweaks       = Array_Cache::get( 'tweaks', 'tweaks' );
		$fixed_tweaks = $this->get_model()->fixed;
		foreach ( $fixed_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$fixed[] = array(
					'label' => $tweaks[ $slug ]->get_label(),
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=resolved' ) . '#' . $slug,
				);
			}
		}

		return $fixed;
	}

	/**
	 * Generates a regex pattern for matching the hook line in a configuration file.
	 *
	 * @return string The regex pattern.
	 */
	public function get_hook_line_pattern() {
		global $wpdb;

		return '/^\$table_prefix\s*=\s*[\'|\"]' . $wpdb->prefix . '[\'|\"]/';
	}

	/**
	 * Checks if the wp-config.php file exists and is writable.
	 *
	 * @return bool True if the file exists and is writable, false otherwise.
	 */
	public function advanced_check_file() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$path_to_wp_config = defender_wp_config_path();

		return file_exists( $path_to_wp_config ) && $wp_filesystem->is_writable( $path_to_wp_config );
	}

	/**
	 * Retrieves a file object for the wp-config.php file.
	 *
	 * @return false|SplFileObject The file object or false on failure.
	 */
	public function file() {
		static $file = false;

		if ( ! $file ) {
			try {
				$file = new SplFileObject( defender_wp_config_path(), 'r+' );
			} catch ( Exception $e ) {
				$this->log( $e->getMessage(), wd_internal_log() );

				return false;
			}
		}

		return $file;
	}

	/**
	 * Writes lines to the wp-config.php file.
	 *
	 * @param  array $lines  The lines to write to the file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function write( $lines ) {
		$file = $this->file();
		$file->flock( LOCK_EX );
		$file->fseek( 0 );

		$bytes = $file->fwrite( implode( "\n", $lines ) );

		if ( $bytes ) {
			$file->ftruncate( $file->ftell() );
		}

		$file->flock( LOCK_UN );

		return (bool) $bytes;
	}

	/**
	 * Generates a notice message for hosting issues related to file permissions.
	 *
	 * @param  string $option  The option related to the notice.
	 *
	 * @return string The notice message.
	 */
	public function show_hosting_notice( $option ) {

		return sprintf(
		/* translators: %s: Option name. */
			esc_html__(
				'Some hostings do not allow you to make changes to the wp-config.php file. Please contact your hosting support team to switch %s ON or OFF on your site.',
				'wpdef'
			),
			$option
		);
	}

	/**
	 * Generates a notice message for hosting issues with a code snippet.
	 *
	 * @param  string $option  The option related to the notice.
	 * @param  string $code  The code snippet to include in the notice.
	 *
	 * @return string The notice message with the code snippet.
	 */
	public function show_hosting_notice_with_code( $option, $code ) {

		return sprintf(
		/* translators: %s - option */
			esc_html__(
				"Couldn't change the %s in your wp-config.php file. Please change it manually:",
				'wpdef'
			) . '<p><b>' . $code . '</b></p>',
			'<b>' . $option . '</b>'
		);
	}
}