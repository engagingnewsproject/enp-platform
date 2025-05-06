<?php
/**
 * Handles interactions with the database table for quarantined files.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;

/**
 * Model for the quarantine table.
 *
 * @since 4.0.0
 */
class Quarantine extends DB {

	public const WP_UNCATEGORIZED = 0;
	public const WP_CORE          = 1;
	public const WP_PLUGIN        = 2;
	public const WP_THEME         = 3;
	public const WP_DROPINS       = 4;
	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_quarantine';

	/**
	 * Quarantine record ID.
	 *
	 * @var int
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $id;

	/**
	 * Associated defender scan item ID.
	 *
	 * @var int
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $defender_scan_item_id;

	/**
	 * File hash.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_hash;

	/**
	 * Full path of the file.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_full_path;

	/**
	 * Original name of the file.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_original_name;

	/**
	 * File extension.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_extension;

	/**
	 * File MIME type.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_mime_type;

	/**
	 * File read/write permission.
	 *
	 * @var int
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_rw_permission;

	/**
	 * File owner.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_owner;

	/**
	 * File group.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_group;

	/**
	 * File version.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_version;

	/**
	 * File category.
	 *
	 * @var int
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_category;

	/**
	 * File modified time.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $file_modified_time;

	/**
	 * Source slug.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $source_slug;

	/**
	 * Created time.
	 *
	 * @var string
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $created_time;

	/**
	 * ID of the user who created the record.
	 *
	 * @var int
	 * @since  4.0.0
	 * @access public
	 * @defender_property
	 */
	public $created_by;

	/**
	 * Checks if a given scan item is quarantined.
	 *
	 * @param  Scan_Item $scan_item  The scan item to check.
	 *
	 * @return bool Returns true if the scan item is quarantined, false otherwise.
	 */
	public function is_quarantined( Scan_Item $scan_item ): bool {
		global $wpdb;

		$scan_item_id = $scan_item->id;
		$file_path    = $scan_item->raw_data['file'];
		$table        = $wpdb->prefix . $this->table;

		$records = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT EXISTS( SELECT 1 FROM {$table} WHERE `defender_scan_item_id` = %d OR `file_full_path` = %s ORDER BY `created_time` DESC LIMIT 0, 1 )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_item_id,
				$file_path
			)
		);

		return 1 === $records;
	}

	/**
	 * Deletes a record from the database based on the given ID.
	 *
	 * @param  int $id  The ID of the record to delete.
	 *
	 * @return bool Returns true if the record was successfully deleted, false otherwise.
	 */
	public function delete( int $id ): bool {
		$delete = self::get_orm()->get_repository( self::class )
			->delete( array( 'id' => $id ) );

		return is_int( $delete );
	}

	/**
	 * Select records from the database based on the given scan item ID.
	 *
	 * @param  int $scan_item_id  The ID of the scan item.
	 *
	 * @return array The selected records.
	 */
	public function select_by_scan_item_id( int $scan_item_id ): array {
		return self::get_orm()->get_repository( self::class )->select( '' )
			->where( 'defender_scan_item_id', $scan_item_id )->get();
	}

	/**
	 * Select records from the database based on the given file full path.
	 *
	 * @param  string $file_full_path  The full path of the file.
	 *
	 * @return array The selected records.
	 */
	public function select_by_file_full_path( string $file_full_path ): array {
		return self::get_orm()->get_repository( self::class )->select( '' )
			->where( 'file_full_path', $file_full_path )->get();
	}

	/**
	 * Selects the restore detail for a given scan item.
	 *
	 * @param  Scan_Item $scan_item  The scan item to retrieve the restore detail for.
	 *
	 * @return mixed The restore detail record from the database.
	 */
	public function select_restore_detail( Scan_Item $scan_item ) {
		global $wpdb;

		$scan_item_id = $scan_item->id;
		$file_path    = $scan_item->raw_data['file'];
		$table        = $wpdb->prefix . $this->table;

		$records = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE `defender_scan_item_id` = %d OR `file_full_path` = %s ORDER BY `created_time` DESC LIMIT 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_item_id,
				$file_path
			)
		);

		return $records;
	}

	/**
	 * Retrieves a collection of quarantined files along with their metadata.
	 *
	 * @return array An array of quarantined files with their metadata.
	 */
	public function quarantine_collection(): array {
		global $wpdb;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT {$wpdb->prefix}defender_quarantine.id, file_hash, file_original_name, file_extension, source_slug, created_by, file_full_path, file_modified_time, created_time, display_name as user_display_name FROM {$wpdb->prefix}defender_quarantine LEFT JOIN $wpdb->users ON {$wpdb->prefix}defender_quarantine.created_by = $wpdb->users.id ORDER BY {$wpdb->prefix}defender_quarantine.id DESC",
			ARRAY_A
		);
	}

	/**
	 * Get record by primary key.
	 *
	 * @param  int $id  Primary key of the record.
	 *
	 * @return Quarantine|null Return Quarantine model on fetched else null.
	 */
	public function find_by_id( int $id ) {
		$orm = self::get_orm();

		$record = $orm->get_repository( self::class )->find_by_id( $id )->get();

		return isset( $record[0] ) ? $record[0] : null;
	}

	/**
	 * Get records which are created older than the $expiry_limit
	 *
	 * @param  string $expiry_limit  Expiry limit date time in mysql format.
	 *
	 * @return array Array of quarantined files primary key if exists else empty array.
	 */
	public function get_old_records( string $expiry_limit ): array {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );

		$records = $builder->select( 'id' )
			->where( 'created_time', '<=', $expiry_limit )->get_results();

		return $records;
	}

	/**
	 * Drop quarantine table.
	 */
	public function drop_table(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DROP TABLE IF EXISTS {$wpdb->prefix}defender_quarantine"  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		);
	}

	/**
	 * SQL to fetch array of last 5 recently quarantined files.
	 */
	public function hub_list(): array {
		global $wpdb;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT {$wpdb->prefix}defender_quarantine.id, file_original_name, file_extension, created_time as quarantined_time, file_hash as quarantined_path, file_full_path as source_path FROM {$wpdb->prefix}defender_quarantine ORDER BY quarantined_time DESC LIMIT 0, 5",
			ARRAY_A
		);
	}
}