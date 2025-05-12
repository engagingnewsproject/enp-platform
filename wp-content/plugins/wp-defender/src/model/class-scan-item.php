<?php
/**
 * Handles interactions with the database for scan items.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;
use WP_Defender\Behavior\Scan_Item\Vuln_Result;
use WP_Defender\Behavior\Scan_Item\Malware_Result;

/**
 * Model for scan item table.
 */
class Scan_Item extends DB {

	// For 'File change detection' option.
	public const TYPE_INTEGRITY = 'core_integrity', TYPE_PLUGIN_CHECK = 'plugin_integrity';
	// For 'Known vulnerabilities' and 'Suspicious code' options.
	public const TYPE_VULNERABILITY = 'vulnerability', TYPE_SUSPICIOUS = 'malware';
	// Different statuses.
	public const STATUS_ACTIVE = 'active', STATUS_IGNORE = 'ignore';

	/**
	 * Defines the table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_scan_item';
	/**
	 * Defines the primary key.
	 *
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Defines a public property for storing the parent ID.
	 *
	 * @var int
	 * @defender_property
	 */
	public $parent_id;
	/**
	 * Type of the issue, base on this we will load the behavior.
	 *
	 * @var string
	 * @defender_property
	 */
	public $type;
	/**
	 * Contain generic data.
	 *
	 * @var array
	 * @defender_property
	 */
	public $raw_data = array();

	/**
	 * Defines a public property for storing the status.
	 *
	 * @var string
	 * @defender_property
	 */
	public $status;

	/**
	 * Get the total of each type of provided status either STATUS_ACTIVE or STATUS_IGNORE.
	 *
	 * @param  int    $parent_id  The primary key of the scan table.
	 * @param  string $status  Active or ignore status of scan item(s).
	 *
	 * @return array Return array of group and all total.
	 */
	public function get_types_total( $parent_id, $status ) {
		global $wpdb;

		$records = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT IFNULL(`type`, 'all') as `item_type`, count(*) as `type_total` FROM {$wpdb->base_prefix}defender_scan_item WHERE `parent_id` = %d AND `status` = %s Group BY `type` WITH ROLLUP",
				$parent_id,
				$status
			)
		);

		$results = array();
		foreach ( $records as $record ) {
			$results[ $record->item_type ] = (int) $record->type_total;
		}

		return $results;
	}

	/**
	 * Deletes a record from the database by its ID.
	 *
	 * @param  int $id  The ID of the record to be deleted.
	 *
	 * @return bool Returns true if the record was successfully deleted, false otherwise.
	 */
	public function delete_by_id( int $id ): bool {
		$delete = self::get_orm()->get_repository( self::class )
			->delete( array( 'id' => $id ) );

		return is_int( $delete );
	}

	/**
	 * Import data into the model.
	 *
	 * @param mixed $data The data array to import values from.
	 *
	 * @return void
	 */
	public function import( $data ): void {
		parent::import( $data );
		switch ( $this->type ) {
			// Add other behaviors here.
			case self::TYPE_SUSPICIOUS:
				$this->attach_behavior( Malware_Result::class, Malware_Result::class );
				break;
			case self::TYPE_VULNERABILITY:
				$this->attach_behavior( Vuln_Result::class, Vuln_Result::class );
				break;
			default:
				break;
		}
	}
}