<?php

namespace WP_Defender\Behavior\Scan;

use Calotes\Base\File;
use Calotes\Component\Behavior;
use WP_Defender\Component\Timer;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Plugin;

class Plugin_Integrity extends Behavior {
	use IO, Plugin;

	const URL_PLUGIN_VCS = 'https://downloads.wordpress.org/plugin-checksums/';
	const PLUGIN_SLUGS   = 'wd_plugin_slugs_changes';

	/**
	 * Check if the slug is a valid WordPress.org slug.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	private function is_valid_wporg_slug( $slug ) {
		return ! empty( $slug ) && '.' !== $slug;
	}

	/**
	 * Reformat array.
	 *
	 * @param array $array
	 * @param string $field
	 * @param string $prefix. Default empty line
	 *
	 * @return array
	 */
	private function pluck( $array, $field, $prefix = '' ) {
		$new_list = array();

		foreach ( $array as $key => $value ) {
			if ( is_object( $value ) ) {
				$new_list[ $prefix . $key ] = $value->$field;
			} else {
				$new_list[ $prefix . $key ] = $value[ $field ];
			}
		}

		return $new_list;
	}

	/**
	 * Retrieve hash for a given plugin from wordpress.org
	 *
	 * @param string $slug    plugin folder
	 * @param string $version plugin version
	 *
	 * @return array
	 */
	private function get_plugin_hash( $slug, $version ) {
		if ( ! $this->is_valid_wporg_slug( $slug ) ) {
			return array();
		}
		//Get original from wp.org e.g. https://downloads.wordpress.org/plugin-checksums/hello-dolly/1.6.json
		$response = wp_remote_get( self::URL_PLUGIN_VCS . $slug . '/' . $version . '.json' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 404 === (int) $code ) {
			//This plugin is not found on wordpress.org
			return  array();
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			return array();
		}

		$data = json_decode( $body, true );

		if ( ! $data || empty( $data['files'] ) ) {
			return array();
		}

		return $this->pluck( $data['files'], 'md5', $slug . DIRECTORY_SEPARATOR );
	}

	/**
	 * Fetch the checksums
	 *
	 * @return array
	 */
	protected function plugin_checksum() {
		$all_plugin_hashes = array();
		foreach ( $this->get_plugins() as $slug => $plugin ) {
			if ( false === strpos( $slug, '/' ) ) {
				//Todo: get correct hashes for single-file plugins
				//separate case for Hello Dolly
				$base_slug = 'hello.php' === $slug ? 'hello-dolly' : $slug;
			} else {
				$base_slug = explode( '/', $slug );
				$base_slug = array_shift( $base_slug );
			}

			//Todo: fix global case if premium and free plugins has the same slug, e.g. 'forminator'
			if ( 'forminator' === $base_slug && 'Forminator Pro' === $plugin['Name'] ) {
				continue;
			}
			$plugin_hashes = $this->get_plugin_hash( $base_slug, $plugin['Version'] );

			if ( ! empty( $plugin_hashes ) ) {
				$all_plugin_hashes = array_merge( $all_plugin_hashes, $plugin_hashes );
			}
		}

		return $all_plugin_hashes;
	}

	public function plugin_integrity_check() {
		$plugins = new File(
			WP_PLUGIN_DIR,
			true,
			false,
			array(),
			array(
				'filename' => array(
					'index.php',
				),
			),
			true,
			true
		);

		$plugin_files = $plugins->get_dir_tree();
		$plugin_files = array_filter( $plugin_files );

		$plugin_files = new \ArrayIterator( $plugin_files );
		$checksums    = $this->plugin_checksum();
		$timer        = new Timer();
		$model        = $this->owner->scan;
		$pos          = (int) $model->task_checkpoint;
		$plugin_files->seek( $pos );
		$slugs_of_edited_plugins = array();
		while ( $plugin_files->valid() ) {
			if ( ! $timer->check() ) {
				$this->log( 'break out cause too long', 'scan' );
				break;
			}

			if ( $model->is_issue_whitelisted( $plugin_files->current() ) ) {
				//this is ignored, so do nothing
				// $this->log( sprintf( 'skip %s because of file is whitelisted', $plugin_files->current() ), 'scan' );
				$plugin_files->next();
				continue;
			}

			if ( $model->is_issue_ignored( $plugin_files->current() ) ) {
				//this is ignored, so do nothing
				// $this->log( sprintf( 'skip %s because of file is ignored', $plugin_files->current() ), 'scan' );
				$plugin_files->next();
				continue;
			}

			//because in windows, the file will be \ instead of /, so we need to convert everything to /
			$file = $plugin_files->current();
			//get relative so we can compare
			$abs_path = WP_PLUGIN_DIR;
			if ( DIRECTORY_SEPARATOR === '\\' ) {
				//this mean we are on windows
				$abs_path = str_replace( '/', DIRECTORY_SEPARATOR, $abs_path );
			}
			$rev_file = str_replace( $abs_path, '', $file );
			//remove the first \ on windows
			$rev_file = str_replace( DIRECTORY_SEPARATOR, '/', $rev_file );
			//remove the first / on path
			$rev_file = ltrim( $rev_file, '/' );
			if ( isset( $checksums[ $rev_file ] ) ) {
				if ( ! $this->compare_hashes( $file, $checksums[ $rev_file ] ) ) {
					$base_slug                 = explode( '/', $rev_file );
					$slugs_of_edited_plugins[] = array_shift( $base_slug );
					$this->log( sprintf( 'modified %s', $file ), 'scan' );
					$model->add_item(
						Scan_Item::TYPE_PLUGIN_CHECK,
						array(
							'file' => $file,
							'type' => 'modified',
						)
					);
				}
			} else {
				//Todo: no verify from wp.org
			}
			$model->calculate_percent( $plugin_files->key() * 100 / $plugin_files->count(), 3 );
			if ( $plugin_files->key() % 100 === 0 ) {
				//we should update the model percent each 100 files so we have some progress on the screen
				$model->save();
			}
			$plugin_files->next();
		}
		if ( true === $plugin_files->valid() ) {
			//save the current progress and quit
			$model->task_checkpoint = $plugin_files->key();
		} else {
			//we will check if we have any ignore issue from last scan, so we can bring it here
			$last = Scan::get_last();
			if ( is_object( $last ) ) {
				$ignored_issues = $last->get_issues( Scan_Item::TYPE_PLUGIN_CHECK, Scan_Item::STATUS_IGNORE );
				foreach ( $ignored_issues as $issue ) {
					$model->add_item( Scan_Item::TYPE_PLUGIN_CHECK, $issue->raw_data, Scan_Item::STATUS_IGNORE );
				}
			}
			//done, reset this so later can use
			$model->task_checkpoint = null;
		}
		$model->save();
		/**
		 * Reduce false positive reports. Check it only if enabled 'Suspicious code' option.
		 * @since 2.4.10
		 */
		if ( ! empty( $slugs_of_edited_plugins ) && ( new Scan_Settings() )->scan_malware ) {
			update_site_option( self::PLUGIN_SLUGS, array_unique( $slugs_of_edited_plugins ) );
		}
		//Todo: add file and time limit improvement

		return ! $plugin_files->valid();
	}
}
