<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Scan as Model_Scan;
use WP_Defender\Model\Scan_Item;

/**
 * Class Legacy_Versions
 * @package WP_Defender\Component
 * @since 2.5.6
 */
class Legacy_Versions extends Component {
	const IGNORE_LIST = 'wdfscanignore';

	/**
	 * @return array
	 */
	private function find_all_scan_issue_items() {
		global $wpdb;

		$sql  = 'SELECT t0.ID AS id,t0.post_parent AS parentId,t1.meta_value AS type,t2.meta_value AS raw';
		$sql .= ' FROM ' . $wpdb->posts . ' AS t0';
		$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t1 ON t1.post_id=ID AND t1.meta_key='type'";
		$sql .= ' LEFT JOIN ' . $wpdb->postmeta . " as t2 ON t2.post_id=ID AND t2.meta_key='raw'";
		$sql .= " WHERE t0.post_type='wdf_scan_item' AND t0.post_status = 'issue'";
		$sql .= ' GROUP BY ID';

		return $wpdb->get_results( $sql, ARRAY_A );// phpcs:ignore
	}

	/**
	 * @param array $ids
	 *
	 * @return array
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

			return $wpdb->get_results( $sql, ARRAY_A );// phpcs:ignore
		}

		return array();
	}

	/**
	 * @param array $old_data
	 *
	 * @return array
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
					// Change 'unknown' -> 'unversion'.
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
	 * @param array  $issue_items
	 * @param object $model
	 *
	 * @return mixed
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
						$file_name = isset( $issue->raw_data['base_slug'] ) ? $issue->raw_data['base_slug'] : '';
					} else {
						$file_name = isset( $issue->raw_data['file'] ) ? $issue->raw_data['file'] : '';
					}
					// Check for uniqueness of elements.
					if ( $data['file'] !== $file_name && ! in_array( $file_name, $unique_arr, true ) ) {
						$unique_arr[] = $file_name;
						$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_ACTIVE );
					}
				}
			} else {
				$file_name = isset( $data['file'] )
					? $data['file']
					: ( isset( $data['raw']['file'] ) ? $data['raw']['file'] : false );
				// Scan wasn't start.
				if ( $file_name && ! in_array( $file_name, $unique_arr, true ) ) {
					$unique_arr[] = $file_name;
					$model->add_item( $data['type'], $data['raw'], Scan_Item::STATUS_ACTIVE );
				}
			}
		}
	}

	/**
	 * @param array  $ignored_items
	 * @param object $model
	 *
	 * @return mixed
	 */
	private function save_scan_ignored_data( $ignored_items, $model ) {
		if ( empty( $ignored_items ) ) {
			return;
		}
		// Get INDEXER of ignored issues.
		$index_lists = get_site_option( Model_Scan::IGNORE_INDEXER, array() );
		// Get existed ignored issues.
		$existed_issues = $model->get_issues( null, Scan_Item::STATUS_IGNORE );
		foreach ( $ignored_items as $item ) {
			$data = $this->adapt_scan_data( $item );
			if ( empty( $data['file'] ) ) {
				continue;
			}
			if ( ! empty( $existed_issues ) ) {
				// Scan was started in version > 2.3.2
				foreach ( $existed_issues as $issue ) {
					if ( Scan_Item::TYPE_VULNERABILITY === $data['type'] ) {
						$file_name = isset( $issue->raw_data['base_slug'] ) ? $issue->raw_data['base_slug'] : '';
					} else {
						$file_name = isset( $issue->raw_data['file'] ) ? $issue->raw_data['file'] : '';
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
				$file_name = isset( $data['file'] )
					? $data['file']
					: ( isset( $data['raw']['slug'] ) ? $data['raw']['slug'] : false );
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
	 * @return array
	 */
	public function get_scan_issue_data() {

		return $this->find_all_scan_issue_items();
	}

	/**
	 * @return array
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
	 * @return int
	 */
	private function run_simlpe_scan() {
		$scan_component = new \WP_Defender\Component\Scan();
		$scan_model     = new Model_Scan();
		// The simplest data:
		$scan_model->percent         = 100;
		$scan_model->total_tasks     = 1;
		$scan_model->task_checkpoint = '';
		$scan_model->status          = Model_Scan::STATUS_FINISH;
		$scan_model->date_start      = gmdate( 'Y-m-d H:i:s' );
		$scan_model->date_end        = gmdate( 'Y-m-d H:i:s' );
		$scan_model->is_automation   = false;
		$last_id                     = $scan_model->save();
		$this->log( 'Scan ID during data migration: ' . $last_id, 'scan.log' );
		$scan_component->advanced_scan_actions( $scan_model );

		return $last_id;
	}

	/**
	 * Migrate scan data.
	 * First we save the ignored list to exclude them for the issue list.
	 * @param array $issue_list
	 * @param array $ignored_list
	 *
	 * @return mixed
	 */
	public function migrate_scan_data( $issue_list, $ignored_list ) {
		$scan = Model_Scan::get_active();
		if ( is_object( $scan ) ) {
			$this->log( 'Scan is still running', 'scan.log' );
			return;
		}
		$model = Model_Scan::get_last();
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
			$model = Model_Scan::get_last();
			if ( is_object( $model ) && ! is_wp_error( $model ) ) {
				$this->save_scan_ignored_data( $ignored_list, $model );
				$this->save_scan_issue_data( $issue_list, $model );
			}
		}
	}

	/**
	 * @param array $issue_list
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

	public function change_onboarding_status() {
		update_site_option( 'wp_defender_shown_activator', true );
	}
}