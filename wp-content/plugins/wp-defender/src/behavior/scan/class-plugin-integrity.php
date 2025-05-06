<?php
/**
 * Handles plugin integrity scan.
 *
 * @package WP_Defender\Behavior\Scan
 */

namespace WP_Defender\Behavior\Scan;

use ArrayIterator;
use Calotes\Base\File;
use WP_Defender\Traits\IO;
use WP_Defender\Model\Scan;
use WP_Defender\Traits\Plugin;
use Calotes\Component\Behavior;
use WP_Defender\Component\Timer;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Integrations\Smush;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Helper\Analytics\Scan as Scan_Analytics;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * It is responsible for performing integrity checks on plugins.
 */
class Plugin_Integrity extends Behavior {

	use IO;
	use Plugin;

	public const URL_PLUGIN_VCS       = 'https://downloads.wordpress.org/plugin-checksums/';
	public const PLUGIN_SLUGS         = 'wd_plugin_slugs_changes';
	public const PLUGIN_PREMIUM_SLUGS = 'wd_plugin_premium_slugs';
	/**
	 * List of premium plugin slugs.
	 *
	 * @var array
	 */
	private $premium_slugs = array();

	/**
	 * Check if the given slug is a valid WordPress.org slug.
	 *
	 * @param  string $slug  The slug to check.
	 *
	 * @return bool Returns true if the slug is valid, false otherwise.
	 */
	private function is_valid_wporg_slug( $slug ): bool {
		return ! empty( $slug ) && '.' !== $slug;
	}

	/**
	 * Plucks a specific field from an array of objects or arrays and returns a new array with the plucked values.
	 *
	 * @param  array  $collection  The array to pluck values from.
	 * @param  string $field  The field to pluck from each element in the array.
	 * @param  string $prefix  Optional. A prefix to add to each key in the new array. Default is an empty string.
	 *
	 * @return array The new array with the plucked values.
	 */
	private function pluck( $collection, $field, $prefix = '' ): array {
		$new_list = array();

		foreach ( $collection as $key => $value ) {
			$prefix_key = defender_replace_line( $prefix . $key );

			if ( is_object( $value ) ) {
				$new_list[ $prefix_key ] = $value->$field;
			} else {
				$new_list[ $prefix_key ] = $value[ $field ];
			}
		}

		return $new_list;
	}

	/**
	 * Retrieve hash for a given plugin from wordpress.org.
	 *
	 * @param  string $slug  Plugin folder.
	 * @param  string $version  Plugin version.
	 *
	 * @return array
	 */
	private function get_plugin_hash( $slug, $version ): array {
		if ( ! $this->is_valid_wporg_slug( $slug ) ) {
			$this->premium_slugs[] = $slug;

			return array();
		}
		// Get original from wp.org e.g. https://downloads.wordpress.org/plugin-checksums/hello-dolly/1.6.json.
		$response = wp_remote_get( self::URL_PLUGIN_VCS . $slug . '/' . $version . '.json' );

		if ( is_wp_error( $response ) ) {
			$this->premium_slugs[] = $slug;

			return array();
		}

		if ( 404 === (int) wp_remote_retrieve_response_code( $response ) ) {
			// This plugin is not found on WordPress.org.
			$this->premium_slugs[] = $slug;

			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			$this->premium_slugs[] = $slug;

			return array();
		}

		$data = json_decode( $body, true );

		if ( ! $data || empty( $data['files'] ) ) {
			return array();
		}

		if ( ! $this->is_likely_wporg_slug( $slug ) ) {
			$this->premium_slugs[] = $slug;

			return array();
		}

		return $this->pluck( $data['files'], 'md5', $slug . DIRECTORY_SEPARATOR );
	}

	/**
	 * Fetch the checksums.
	 *
	 * @return array
	 */
	protected function plugin_checksum(): array {
		$all_plugin_hashes = array();
		/**
		 * Exclude plugin slugs.
		 *
		 * @param  array  $slugs  Slugs of excluded plugins.
		 *
		 * @since 3.1.0
		 */
		$excluded_slugs = (array) apply_filters( 'wd_scan_excluded_plugin_slugs', array() );

		foreach ( $this->get_plugins() as $slug => $plugin ) {
			if ( false === strpos( $slug, '/' ) ) {
				// Todo: get correct hashes for single-file plugins.
				// Separate case for 'Hello Dolly'.
				$base_slug = 'hello.php' === $slug ? 'hello-dolly' : $slug;
			} else {
				$base_slug = explode( '/', $slug );
				$base_slug = array_shift( $base_slug );
			}

			if ( in_array( $base_slug, $excluded_slugs, true ) ) {
				continue;
			}

			$plugin_hashes = $this->get_plugin_hash( $base_slug, $plugin['Version'] );

			if ( ! empty( $plugin_hashes ) ) {
				$all_plugin_hashes = array_merge( $all_plugin_hashes, $plugin_hashes );
			}
		}

		return $all_plugin_hashes;
	}

	/**
	 * Performs integrity check on plugins.
	 *
	 * @return bool
	 */
	public function plugin_integrity_check(): bool {
		$abs_path = defender_replace_line( WP_PLUGIN_DIR );
		$settings = wd_di()->get( Scan_Settings::class );
		$plugins  = new File(
			$abs_path,
			true,
			false,
			array(),
			array( 'filename' => array( 'index.php' ) ),
			true,
			true,
			$settings->filesize
		);

		$plugin_files = $plugins->get_dir_tree();
		$plugin_files = array_filter( $plugin_files );

		$plugin_files = new ArrayIterator( $plugin_files );
		$checksums    = $this->plugin_checksum();
		$timer        = new Timer();
		$model        = $this->owner->scan;
		$pos          = (int) $model->task_checkpoint;
		$plugin_files->seek( $pos );
		$slugs_of_edited_plugins = array();
		$integration_smush       = wd_di()->get( Smush::class );
		$exist_smush_images      = $integration_smush->exist_image_table();
		while ( $plugin_files->valid() ) {
			if ( ! $timer->check() ) {

				$reason = 'break out cause too long';

				/**
				 * Retrieves the Scan_Analytics class.
				 *
				 * @var Scan_Analytics $scan_analytics
				 */
				$scan_analytics = wd_di()->get( Scan_Analytics::class );

				$scan_analytics->track_feature(
					$scan_analytics::EVENT_SCAN_FAILED,
					array(
						$scan_analytics::EVENT_SCAN_FAILED_PROP => $scan_analytics::EVENT_SCAN_FAILED_ERROR,
						'Error_Reason' => $reason,
					)
				);

				$this->log( $reason, Scan_Controller::SCAN_LOG );
				break;
			}

			if ( $model->is_issue_whitelisted( $plugin_files->current() ) ) {
				// This is whitelisted, so do nothing.
				$plugin_files->next();
				continue;
			}

			if ( $model->is_issue_ignored( $plugin_files->current() ) ) {
				// This is ignored, so do nothing.
				$plugin_files->next();
				continue;
			}

			if (
				$exist_smush_images
				&& $this->file_is_valid_image( $plugin_files->current() )
				&& $integration_smush->exist_image_path( $plugin_files->current() )
			) {
				$this->log(
					sprintf( 'skip %s because of Smush optimized file', $plugin_files->current() ),
					Scan_Controller::SCAN_LOG
				);
				$plugin_files->next();
				continue;
			}

			$file     = $plugin_files->current();
			$rev_file = str_replace( $abs_path, '', $file );
			// Remove directory separator on the left.
			$rev_file  = ltrim( $rev_file, DIRECTORY_SEPARATOR );
			$base_slug = explode( '/', $rev_file );
			$base_slug = array_shift( $base_slug );

			// Verify files only from wp.org. No Premium-things.
			if ( isset( $checksums[ $rev_file ] ) ) {
				if ( ! $this->compare_hashes( $file, $checksums[ $rev_file ] ) ) {
					$slugs_of_edited_plugins[] = $base_slug;
					$this->log( sprintf( 'modified %s', $file ), Scan_Controller::SCAN_LOG );
					$model->add_item(
						Scan_Item::TYPE_PLUGIN_CHECK,
						array(
							'file' => $file,
							'type' => 'modified',
						)
					);
				}
			} elseif ( ! in_array( $base_slug, $this->premium_slugs, true ) ) {
				// Unknown file in a plugin directory.
				$slugs_of_edited_plugins[] = $base_slug;
			}

			$model->calculate_percent( $plugin_files->key() * 100 / $plugin_files->count(), 3 );
			if ( 0 === $plugin_files->key() % 100 ) {
				// We should update the model percent each 100 files so we have some progress on the screen.
				$model->save();
			}
			$plugin_files->next();
		}
		if ( $plugin_files->valid() ) {
			// Save the current progress and quit.
			$model->task_checkpoint = $plugin_files->key();
		} else {
			// We will check if we have any ignore issue from last scan, so we can bring it here.
			$last = Scan::get_last();
			if ( is_object( $last ) ) {
				$ignored_issues = $last->get_issues( Scan_Item::TYPE_PLUGIN_CHECK, Scan_Item::STATUS_IGNORE );
				foreach ( $ignored_issues as $issue ) {
					$model->add_item( Scan_Item::TYPE_PLUGIN_CHECK, $issue->raw_data, Scan_Item::STATUS_IGNORE );
				}
			}
			// Done, reset this, so we can use later.
			$model->task_checkpoint = '';
		}
		$model->save();
		/**
		 * Reduce false positive reports. Check it only if enabled 'Suspicious code' option.
		 *
		 * @since 2.4.10
		 */
		if ( ( new Scan_Settings() )->scan_malware ) {
			if ( ! empty( $slugs_of_edited_plugins ) ) {
				update_site_option( self::PLUGIN_SLUGS, array_unique( $slugs_of_edited_plugins ) );
			}
			if ( ! empty( $this->premium_slugs ) ) {
				update_site_option( self::PLUGIN_PREMIUM_SLUGS, $this->premium_slugs );
			}
		}

		return ! $plugin_files->valid();
	}

	/**
	 * Checks if given file is a valid image.
	 *
	 * @param string $file_path Full path to the file.
	 *
	 * @return bool True if file is a valid image, false otherwise.
	 */
	private function file_is_valid_image( $file_path ): bool {
		$mime = wp_check_filetype( $file_path );
		if ( false === $mime['type'] ) {
			return false;
		}

		return false !== strpos( $mime['type'], 'image' );
	}
}