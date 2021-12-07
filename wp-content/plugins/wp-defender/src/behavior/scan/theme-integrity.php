<?php

namespace WP_Defender\Behavior\Scan;

use Calotes\Base\File;
use Calotes\Component\Behavior;
use WP_Defender\Component\Timer;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Theme;
use WP_Error;

//Deprecated since 2.5.0.
class Theme_Integrity extends Behavior {
	use IO, Theme;

	const URL_THEME_DOWNLOAD  = 'https://downloads.wordpress.org/theme/';
	const THEME_SLUGS         = 'wd_theme_slugs_changes';
	const THEME_PREMIUM_SLUGS = 'wd_theme_premium_slugs';
	/**
	 * List of premium theme slugs.
	 *
	 * @var array
	 */
	private $premium_slugs = [];

	private function download_file( $url ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php';
		}
		$is_file = download_url( $url );
		if ( is_wp_error( $is_file ) ) {
			return new WP_Error( 'defender_theme_download', $is_file->get_error_message() );
		}
		if ( ! realpath( $is_file ) ) {
			return new WP_Error( 'defender_file_not_found', __( 'Downloaded file could not be found.', 'wpdef' ) );
		}

		return $is_file;
	}

	private function get_tmp_theme_folder() {
		return $this->get_tmp_folder() . 'zip-themes' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get hashes of the uploaded theme.
	 *
	 * @param string $zip_file
	 * @param string $theme_folder
	 *
	 * @return array|WP_Error
	 */
	private function get_hash_uploaded_archive( $zip_file, $theme_folder ) {
		$unzip_folder = $this->get_tmp_theme_folder();

		if ( ! file_exists( $unzip_folder . DIRECTORY_SEPARATOR . $theme_folder ) ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				include_once 'wp-admin/includes/file.php';
			}
			WP_Filesystem();

			$unzip_result = unzip_file( $zip_file, $unzip_folder );

			if ( ! $unzip_result ) {
				return new WP_Error(
					'defender_zip',
					sprintf(
					/* translators: %s: file path */
						__( 'Unable to unzip file %s', 'wpdef' ),
						$zip_file
					)
				);
			}
		}

		$array_hashes = array();
		$theme_unzip  = new File(
			$unzip_folder . $theme_folder,
			true,
			false,
			array(),
			array(),
			true,
			true
		);

		$theme_files = $theme_unzip->get_dir_tree();
		$theme_files = array_filter( $theme_files );

		$theme_files = new \ArrayIterator( $theme_files );
		while ( $theme_files->valid() ) {
			$file                      = $theme_files->current();
			$rev_file                  = str_replace( $unzip_folder, '', $file );
			$array_hashes[ $rev_file ] = md5_file( $file );
			$theme_files->next();
		}

		return $array_hashes;
	}

	/**
	 * @param string $theme_folder
	 * @param object $theme        WP_Theme.
	 *
	 * @return array
	 */
	public function get_theme_hash( $theme_folder, $theme ) {
		$url          = self::URL_THEME_DOWNLOAD . $theme_folder . '.' . $theme->get( 'Version' ) . '.zip';
		$tmp_zip_file = $this->download_file( $url );
		if ( is_wp_error( $tmp_zip_file ) ) {
			$this->log( 'Scan theme error: ' . $tmp_zip_file->get_error_message(), 'scan.log' );
			return array();
		}

		$theme_hashes = $this->get_hash_uploaded_archive( $tmp_zip_file, $theme_folder );
		unlink( $tmp_zip_file );
		if ( is_wp_error( $theme_hashes ) ) {
			$this->log( 'Scan theme error: ' . $theme_hashes->get_error_message(), 'scan.log' );
			return array();
		}
		return $theme_hashes;
	}

	/**
	 * Fetch the checksums.
	 *
	 * @return array
	 */
	public function theme_checksum() {
		$all_theme_hashes = array();
		foreach ( $this->get_themes() as $theme_folder => $theme ) {
			if ( is_object( $theme->parent() ) ) {
				continue;
			}
			$theme_hashes = $this->get_theme_hash( $theme_folder, $theme );
			if ( ! empty( $theme_hashes ) ) {
				$all_theme_hashes = array_merge( $all_theme_hashes, $theme_hashes );
			} else {
				$this->premium_slugs[] = $theme_folder;
			}
		}
		// Remove tmp theme dirs.
		$this->delete_dir( $this->get_tmp_theme_folder() );

		return $all_theme_hashes;
	}

	/**
	 * Check if the themes' file is on touch.
	 */
	public function theme_integrity_check() {
		$checksums = $this->theme_checksum();
		$theme_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes';
		$exclude   = array( 'filename' => array( 'index.php' ) );
		if ( ! empty( $this->premium_slugs ) ) {
			// Exclude premium theme files.
			foreach ( $this->premium_slugs as $premium_slug ) {
				$exclude['dir'][] = $theme_dir . DIRECTORY_SEPARATOR . $premium_slug;
			}
		}
		// Get theme files.
		$themes    = new File(
			$theme_dir,
			true,
			false,
			array(),
			$exclude,
			true,
			true
		);

		$theme_files = $themes->get_dir_tree();
		$theme_files = array_filter( $theme_files );
		$theme_files = new \ArrayIterator( $theme_files );
		$timer       = new Timer();
		$model       = $this->owner->scan;
		$pos         = (int) $model->task_checkpoint;
		$theme_files->seek( $pos );
		$slugs_of_edited_themes = array();
		while ( $theme_files->valid() ) {
			if ( ! $timer->check() ) {
				$this->log( 'break out cause too long', 'scan.log' );
				break;
			}

			if ( $model->is_issue_whitelisted( $theme_files->current() ) ) {
				// This is whitelisted, so do nothing.
				$theme_files->next();
				continue;
			}

			if ( $model->is_issue_ignored( $theme_files->current() ) ) {
				// This is ignored, so do nothing.
				$theme_files->next();
				continue;
			}

			// The file will be '\' instead of '/' on Windows, so we need to convert everything to '/'.
			$file = $theme_files->current();
			// Get relative so we can compare.
			$abs_path = $theme_dir;
			if ( defender_is_windows() ) {
				// This mean we are on Windows.
				$abs_path = str_replace( '/', DIRECTORY_SEPARATOR, $abs_path );
			}
			$rev_file = str_replace( $abs_path, '', $file );
			// Remove the first \ on Windows.
			$rev_file = str_replace( DIRECTORY_SEPARATOR, '/', $rev_file );
			// Remove the first / on path.
			$rev_file = ltrim( $rev_file, '/' );
			if ( isset( $checksums[ $rev_file ] ) ) {
				if ( ! $this->compare_hashes( $file, $checksums[ $rev_file ] ) ) {
					$base_slug                = explode( '/', $rev_file );
					$slugs_of_edited_themes[] = array_shift( $base_slug );
					$this->log( sprintf( 'modified %s', $file ), 'scan.log' );
					$model->add_item(
						Scan_Item::TYPE_THEME_CHECK,
						array(
							'file' => $file,
							'type' => 'modified',
						)
					);
				}
			} else {
				$base_slug  = explode( '/', $rev_file );
				$theme_slug = array_shift( $base_slug );
				if ( ! in_array( $theme_slug, $slugs_of_edited_themes, true ) ) {
					// Check is it an unknown free theme file.
					$response = wp_remote_head( "https://wordpress.org/themes/$theme_slug/" );
					if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
						// No verify from wp.org.
						$slugs_of_edited_themes[] = $theme_slug;
						$model->add_item(
							Scan_Item::TYPE_THEME_CHECK,
							array(
								'file' => $file,
								'type' => is_dir( $file ) ? 'dir' : 'unversion',
							)
						);
					}
				}
			}
			$model->calculate_percent( $theme_files->key() * 100 / $theme_files->count(), 4 );
			if ( 0 === $theme_files->key() % 100 ) {
				// We should update the model percent each 100 files so we have some progress on the screen.
				$model->save();
			}
			$theme_files->next();
		}
		if ( $theme_files->valid() ) {
			// Save the current progress and quit.
			$model->task_checkpoint = $theme_files->key();
		} else {
			// We will check if we have any ignore issue from last scan, so we can bring it here.
			$last = Scan::get_last();
			if ( is_object( $last ) ) {
				$ignored_issues = $last->get_issues( Scan_Item::TYPE_THEME_CHECK, Scan_Item::STATUS_IGNORE );
				foreach ( $ignored_issues as $issue ) {
					$model->add_item( Scan_Item::TYPE_THEME_CHECK, $issue->raw_data, Scan_Item::STATUS_IGNORE );
				}
			}
			// Done, reset this, so we can use later.
			$model->task_checkpoint = null;
		}
		$model->save();
		/**
		 * Reduce false positive reports. Check it only if enabled 'Suspicious code' option.
		 * @since 2.4.10
		*/
		if ( ( new Scan_Settings() )->scan_malware ) {
			if ( ! empty( $slugs_of_edited_themes ) ) {
				update_site_option( self::THEME_SLUGS, array_unique( $slugs_of_edited_themes ) );
			}
			if ( ! empty( $this->premium_slugs ) ) {
				update_site_option( self::THEME_PREMIUM_SLUGS, $this->premium_slugs );
			}
		}
		// Todo: add file and time limit improvement.
		return ! $theme_files->valid();
	}
}