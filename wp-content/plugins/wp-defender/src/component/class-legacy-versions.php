<?php
/**
 * Responsible for handling legacy data migration and management related to scan issues and ignored items.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * Responsible for handling legacy data migration and management related to scan issues and ignored items.
 *
 * @since 2.5.6
 */
class Legacy_Versions extends Component {

	public const IGNORE_LIST = 'wdfscanignore';

	/**
	 * Retrieves all scan issue items from the database.
	 *
	 * @return array Array of scan issue items.
	 */
	private function find_all_scan_issue_items() {
		global $wpdb;

		$sql  = 'SELECT t0.ID AS id,t0.post_parent AS parentId,t1.meta_value AS type,t2.meta_value AS raw';
		$sql .= ' FROM ' . $wpdb->posts . ' AS t0';
		$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t1 ON t1.post_id=ID AND t1.meta_key='type'";
		$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t2 ON t2.post_id=ID AND t2.meta_key='raw'";
		$sql .= " WHERE t0.post_type='wdf_scan_item' AND t0.post_status = 'issue'";
		$sql .= ' GROUP BY ID';

		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Retrieves all ignored scan items based on provided IDs.
	 *
	 * @param  array $ids  Array of IDs to retrieve.
	 *
	 * @return array Array of ignored scan items.
	 */
	private function find_all_scan_ignored_items( $ids ) {
		global $wpdb;

		if ( is_array( $ids ) && count( $ids ) > 0 ) {
			$sql  = 'SELECT t0.ID AS id,t0.post_parent AS parentId,t1.meta_value AS type,t2.meta_value AS raw';
			$sql .= ' FROM ' . $wpdb->posts . ' AS t0';
			$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t1 ON t1.post_id=ID AND t1.meta_key='type'";
			$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t2 ON t2.post_id=ID AND t2.meta_key='raw'";
			$sql .= " WHERE t0.post_type='wdf_scan_item' AND t0.post_status = 'ignored'";
			$sql .= ' AND t0.ID IN (' . implode( ', ', $ids ) . ')';
			// SQL is prepared above. so we will ignore prepare warning.
			return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		}

		return array();
	}

	/**
	 * Adapts old scan data format to the new format.
	 *
	 * @param  array $old_data  Old scan data to adapt.
	 *
	 * @return array Adapted scan data.
	 */
	private function adapt_scan_data( $old_data ) {
		$raw = '';
		switch ( $old_data['type'] ) {
			case 'vuln':
				$new_type = Scan_Item::TYPE_VULNERABILITY;
				if ( isset( $old_data['raw'] ) && ! empty( $old_data['raw'] ) ) {
					$raw = maybe_unserialize( $old_data['raw'] );
					/**
					 * Different $raw['slug'] values depending on the type:
					 * 'type' => 'theme', 'slug' => {THEME_SLUG}, ...
					 * 'type' => 'plugin', 'slug' => {PLUGIN_SLUG}, ...
					 * 'type' => 'WordPress', 'slug' => 'WordPress', ...
					 */
					$file = is_array( $raw ) && isset( $raw['slug'] ) ? $raw['slug'] : '';
				} else {
					$file = '';
				}

				if ( is_array( $raw ) && 'WordPress' !== $raw['type'] ) {
					$raw_data = array(
						'type'      => $raw['type'],
						'slug'      => $raw['slug'],
						'base_slug' => $raw['slug'],
						'version'   => '',
						'name'      => $raw['slug'],
						'bugs'      => $raw['bugs'],
					);
				} else {
					$raw_data = $old_data['raw'];
				}
				break;
			case 'content':
				$new_type = Scan_Item::TYPE_SUSPICIOUS;
				if ( isset( $old_data['raw'] ) && ! empty( $old_data['raw'] ) ) {
					$raw  = maybe_unserialize( $old_data['raw'] );
					$file = is_array( $raw ) && isset( $raw['file'] ) ? $raw['file'] : '';
				} else {
					$file = '';
				}

				$raw_data         = is_array( $raw ) && isset( $raw['meta'] ) ? $raw['meta'] : array();
				$raw_data['file'] = $file;
				break;
			case 'core':
			default:
				$new_type = Scan_Item::TYPE_INTEGRITY;
				if ( isset( $old_data['raw'] ) && ! empty( $old_data['raw'] ) ) {
					$raw  = maybe_unserialize( $old_data['raw'] );
					$file = is_array( $raw ) && isset( $raw['file'] ) ? $raw['file'] : '';
				} else {
					$file = '';
				}

				if ( is_array( $raw ) && isset( $raw['type'] ) ) {
					$short_desc = 'unknown' === $raw['type'] ? 'unversion' : $raw['type'];
				} else {
					$short_desc = '';
				}
				$raw_data = array(
					'file' => $file,
					'type' => $short_desc,
				);
				break;
		}

		return array(
			'type' => $new_type,
			'file' => $file,
			'raw'  => $raw_data,
		);
	}

	/**
	 * Saves scan issue data to the database.
	 *
	 * @param  array $issue_items  Array of issue items to save.
	 * @param  Scan  $model  Scan model instance to use for saving.
	 */
	private function save_scan_issue_data( $issue_items, $model ) {
		if ( empty( $issue_items ) ) {
			return;
		}
		// Get existed issues.
		$existed_issues = $model->get_issues( null, Scan_Item::STATUS_ACTIVE );
		$unique_arr     = array();
		foreach ( $issue_items as $item ) {
			$data = $this->adapt_scan_data( $item );
			if ( empty( $data['file'] ) ) {
				continue;
			}
			if ( ! empty( $existed_issues ) ) {
				// Scan was started in version > 2.3.2.
				foreach ( $existed_issues as $issue ) {
					if ( Scan_Item::TYPE_VULNERABILITY === $data['type'] ) {
						$file_name = $issue->raw_data['base_slug'] ?? '';
					} else {
						$file_name = $issue->raw_data['file'] ?? '';
					}
					// Check for uniqueness of elements.
					if ( $data['file'] !== $file_name && ! in_array( $file_name, $unique_arr, true ) ) {
						$unique_arr[] = $file_name;
						$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_ACTIVE );
					}
				}
			} else {
				$file_name = $data['file'] ?? $data['raw']['file'] ?? false;
				// Scan wasn't start.
				if ( $file_name && ! in_array( $file_name, $unique_arr, true ) ) {
					$unique_arr[] = $file_name;
					$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_ACTIVE );
				}
			}
		}
	}

	/**
	 * Saves ignored scan data to the database.
	 *
	 * @param  array $ignored_items  Array of ignored items to save.
	 * @param  Scan  $model  Scan model instance to use for saving.
	 */
	private function save_scan_ignored_data( $ignored_items, $model ) {
		if ( empty( $ignored_items ) ) {
			return;
		}
		// Get INDEXER of ignored issues.
		$index_lists = get_site_option( Scan::IGNORE_INDEXER, array() );
		// Get existed ignored issues.
		$existed_issues = $model->get_issues( null, Scan_Item::STATUS_IGNORE );
		foreach ( $ignored_items as $item ) {
			$data = $this->adapt_scan_data( $item );
			if ( empty( $data['file'] ) ) {
				continue;
			}
			if ( ! empty( $existed_issues ) ) {
				// Scan was started in version > 2.3.2 .
				foreach ( $existed_issues as $issue ) {
					if ( Scan_Item::TYPE_VULNERABILITY === $data['type'] ) {
						$file_name = $issue->raw_data['base_slug'] ?? '';
					} else {
						$file_name = $issue->raw_data['file'] ?? '';
					}
					// Check for uniqueness of elements.
					if ( $data['file'] !== $file_name && ! in_array( $file_name, $index_lists, true ) ) {
						if ( isset( $data['file'] ) ) {
							$index_lists[] = $data['file'];
						} elseif ( isset( $data['raw']['slug'] ) ) {
							$index_lists[] = $data['raw']['slug'];
						}
						$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_IGNORE );
					}
				}
			} else {
				$file_name = $data['file'] ?? $data['raw']['slug'] ?? false;
				// Scan wasn't start.
				if ( $file_name && ! in_array( $file_name, $index_lists, true ) ) {
					$index_lists[] = $file_name;
					$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_IGNORE );
				}
			}
		}
		// Update IGNORE_INDEXER.
		$model->update_ignore_list( $index_lists );
	}

	/**
	 * Retrieves all scan issue data.
	 *
	 * @return array Array of all scan issue data.
	 */
	public function get_scan_issue_data() {

		return $this->find_all_scan_issue_items();
	}

	/**
	 * Retrieves all scan ignored data.
	 *
	 * @return array Array of all scan ignored data.
	 */
	public function get_scan_ignored_data() {
		$ids = get_option( self::IGNORE_LIST, false );
		if ( ! is_array( $ids ) ) {
			$ids = get_site_option( self::IGNORE_LIST, array() );
			if ( ! is_array( $ids ) ) {
				return array();
			}
		}

		return $this->find_all_scan_ignored_items( $ids );
	}

	/**
	 * Runs a simple scan.
	 *
	 * @return int ID of the scan.
	 */
	private function run_simlpe_scan() {
		$scan_component = wd_di()->get( Scan::class );
		$scan_model     = wd_di()->get( Scan::class );
		// The simplest data.
		$scan_model->percent         = 100;
		$scan_model->total_tasks     = 1;
		$scan_model->task_checkpoint = '';
		$scan_model->status          = Scan::STATUS_FINISH;
		$scan_model->date_start      = gmdate( 'Y-m-d H:i:s' );
		$scan_model->date_end        = gmdate( 'Y-m-d H:i:s' );
		$scan_model->is_automation   = false;
		$last_id                     = $scan_model->save();
		$this->log( 'Scan ID during data migration: ' . $last_id, Scan_Controller::SCAN_LOG );
		$scan_component->advanced_scan_actions( $scan_model );

		return $last_id;
	}

	/**
	 * Migrates scan data from old format to new format.
	 *
	 * @param  array $issue_list  List of issue items to migrate.
	 * @param  array $ignored_list  List of ignored items to migrate.
	 */
	public function migrate_scan_data( $issue_list, $ignored_list ) {
		$scan = Scan::get_active();
		if ( is_object( $scan ) ) {
			$this->log( 'Scan is still running', Scan_Controller::SCAN_LOG );

			return;
		}
		$model = Scan::get_last();
		// Scan was started and finished before.
		if ( is_object( $model ) && ! is_wp_error( $model ) ) {
			$this->save_scan_ignored_data( $ignored_list, $model );
			$this->save_scan_issue_data( $issue_list, $model );
		} else {
			/**
			 * Scan hasn't started before. Next steps:
			 * create Scan record,
			 * no send notification,
			 * create Scan items.
			 */
			$this->run_simlpe_scan();
			$model = Scan::get_last();
			if ( is_object( $model ) && ! is_wp_error( $model ) ) {
				$this->save_scan_ignored_data( $ignored_list, $model );
				$this->save_scan_issue_data( $issue_list, $model );
			}
		}
	}

	/**
	 * Removes old scan data from the database.
	 *
	 * @param  array $issue_list  List of issue items to remove.
	 * @param  array $ignored_list  List of ignored items to remove.
	 */
	public function remove_old_scan_data( $issue_list, $ignored_list ) {
		// Delete the list of scan issues.
		if ( ! empty( $issue_list ) ) {
			$parent_post_id = (int) $issue_list[0]['parentId'];
			foreach ( $issue_list as $key => $issue ) {
				// Delete scan items.
				wp_delete_post( (int) $issue['id'] );
			}
			// Delete scan.
			wp_delete_post( $parent_post_id );
		}
		// Delete the ignored list.
		if ( ! empty( $ignored_list ) ) {
			$parent_post_id = (int) $ignored_list[0]['parentId'];
			foreach ( $ignored_list as $key => $item ) {
				// Delete scan items.
				wp_delete_post( (int) $item['id'] );
			}
			// Delete scan.
			wp_delete_post( $parent_post_id );

			delete_site_option( self::IGNORE_LIST );
			delete_option( self::IGNORE_LIST );
		}
	}

	/**
	 * Changes the onboarding status in the database.
	 */
	public function change_onboarding_status() {
		update_site_option( 'wp_defender_shown_activator', true );
	}

	/**
	 * Decrypts data using a public key.
	 *
	 * @param  string $encrypt_data  Data to decrypt.
	 * @param  string $key  Public key to use for decryption.
	 *
	 * @return bool|string Decrypted data or false on failure.
	 */
	private static function decrypt_data_with_pub_key( $encrypt_data, $key ) {
		// This is not obfuscation. Just decode a base64-encoded string.
		$str = base64_decode( $encrypt_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! $str ) {
			return false;
		}
		$cipher         = 'aes-256-cbc';
		$iv_len         = openssl_cipher_iv_length( $cipher );
		$iv             = substr( $str, 0, $iv_len );
		$ciphertext_raw = substr( $str, $iv_len + 32 );

		return openssl_decrypt( $ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv );
	}

	/**
	 * Decrypts data using a stored public key.
	 *
	 * @param  string $data  Data to decrypt.
	 *
	 * @return bool|string Decrypted data or false on failure.
	 */
	public static function get_decrypted_data_with_pub_key( $data ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$path_to_pub_key = __DIR__ . '/def.key';
		if ( ! file_exists( $path_to_pub_key ) ) {
			return false;
		}
		$key = $wp_filesystem->get_contents( $path_to_pub_key );

		return false !== $key ? self::decrypt_data_with_pub_key( $data, $key ) : '';
	}
}