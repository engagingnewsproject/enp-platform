<?php
/**
 * Handles image optimization and compression.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Smush integration module.
 *
 * @since 2.5.1
 */
class Smush {

	/**
	 *  Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor for the class.
	 * Initializes the table name for the Smush directory images.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->base_prefix . 'smush_dir_images';
	}

	/**
	 * Check if the table exists
	 *
	 * @return bool
	 */
	public function exist_image_table() {
		global $wpdb;

		return (bool) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $this->table )
			)
		);
	}

	/**
	 * Check optimized image path
	 *
	 * @param  string $path  image path.
	 *
	 * @return bool
	 */
	public function exist_image_path( $path ) {
		global $wpdb;
		return (bool) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id FROM {$this->table} WHERE path = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$path
			)
		);
	}
}