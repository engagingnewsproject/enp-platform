<?php

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Smush integration module.
 * Class Smush
 *
 * @since 2.5.1
 * @package WP_Defender\Integrations
 */
class Smush {
	private $table;

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

		return (bool) $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->esc_like( $this->table )
		) );
	}

	/**
	 * Check optimized image path
	 * @param string
	 *
	 * @return bool
	 */
	public function exist_image_path( $path ) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table} WHERE path = %s",
				$path
			)
		);
	}
}
