<?php
/**
 * The AntiBot Global Firewall model class.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;

/**
 * Class Antibot_Global_Firewall
 *
 * Provides methods to insert, truncate and retrieve IP(s) from database.
 */
class Antibot_Global_Firewall extends DB {
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_antibot';

	/**
	 * The IP address.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip;

	/**
	 * Status of the IP. If the IP is unlocked, the status will be true.
	 *
	 * @var int
	 * @defender_property
	 */
	public $unlocked;

	/**
	 * The time when the IP was unlocked.
	 *
	 * @var string
	 * @defender_property
	 */
	public $unlocked_at;

	/**
	 * Truncate the table when we fetch new data from API.
	 */
	public function truncate() {
		$orm = self::get_orm();
		$orm->get_repository( self::class )->truncate();
	}

	/**
	 * Get IP record.
	 *
	 * @param string $ip The IP address.
	 *
	 * @return self|null
	 */
	public function get_by_ip( $ip ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
			->where( 'ip', $ip )
			->first();
	}

	/**
	 * Bulk insert IP(s) into the database.
	 *
	 * @param iterable $ips The IP(s) to be inserted.
	 *
	 * @return void
	 */
	public function bulk_insert( $ips ): void {
		$batch_size   = 1000;
		$values       = array();
		$placeholders = array();
		$counter      = 0;

		foreach ( $ips as $ip ) {
			$values[]       = $ip;
			$placeholders[] = '(%s)';
			++$counter;

			if ( $counter === $batch_size ) {
				$this->execute_insert( $placeholders, $values );

				$values       = array();
				$placeholders = array();
				$counter      = 0;
			}
		}

		// Insert remaining records if any.
		if ( $counter > 0 ) {
			$this->execute_insert( $placeholders, $values );
		}
	}

	/**
	 * Unlock IP(s) from the AntiBot Global Firewall.
	 *
	 * @param array $ips The IP(s) to be unlocked.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	public function unlock_ips( array $ips ) {
		if ( empty( $ips ) ) {
			return false;
		}

		global $wpdb;

		$table_name   = $wpdb->prefix . $this->table;
		$current_time = time();
		$placeholders = implode( ', ', array_fill( 0, count( $ips ), '%s' ) );

		return $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $table_name SET unlocked = 1, unlocked_at = %d WHERE ip IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $current_time ), $ips )
			)
		);
	}

	/**
	 * Executes the insert query with the provided values and placeholders.
	 *
	 * @param array $placeholders The placeholders for the insert query.
	 * @param array $values       The values to be inserted.
	 *
	 * @return void
	 */
	private function execute_insert( array $placeholders, array $values ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->table;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO $table_name (ip) VALUES " . implode( ', ', $placeholders ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$values
			)
		);
	}
}