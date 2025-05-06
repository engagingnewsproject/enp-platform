<?php
/**
 * Base model for managing settings.
 *
 * @package Calotes\Model
 */

namespace Calotes\Model;

use Exception;
use Calotes\Base\Model;

/**
 * Base model for managing settings.
 */
class Setting extends Model {

	/**
	 * The excluded properties.
	 *
	 * @var string[]
	 */
	protected $exclude = array( 'table' );

	/**
	 * The old settings.
	 *
	 * @var array
	 */
	protected $old_settings = array();

	/**
	 * Constructor method for the Setting class.
	 * This method is responsible for parsing annotations, executing the before_load, load, after_load, and sanitize
	 * methods.
	 */
	public function __construct() {
		// We parse the annotations here, and only one.
		$this->parse_annotations();
		$this->before_load();
		$this->load();
		$this->after_load();
		$this->sanitize();
	}

	/**
	 * Saves the data by updating the site option with the prepared data.
	 *
	 * @return void
	 */
	public function save() {
		$prepared_data = $this->prepare_data();
		$data          = wp_json_encode( $prepared_data );
		$ret           = update_site_option( $this->table, $data );
		if ( false === $ret ) {
			$this->internal_logging[] = sprintf(
				'Saving fail on %s with data %s.',
				$this->table,
				wp_json_encode( $data )
			);
		} else {
			/**
			 * Handle settings update. No from WP CLI commands.
			 *
			 * @param  array  $old_settings  The old option values.
			 * @param  array  $prepared_data  The new option values.
			 *
			 * @since 4.2.0
			 */
			do_action( 'wd_settings_update', $this->old_settings, $prepared_data );
		}
	}

	/**
	 * Load data.
	 *
	 * @throws Exception Table must be defined before using.
	 */
	public function load() {
		if ( empty( $this->table ) ) {
			throw new Exception( 'Table must be defined before using.' );
		}

		$data = get_site_option( $this->table );
		if ( false === $data ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			$data = json_decode( $data, true );
		}

		if ( ! is_array( $data ) ) {
			return;
		}

		$data               = $this->prepare_data( $data );
		$this->old_settings = $data;
		$this->import( $data );
	}

	/**
	 * Deletes the site option associated with the current table.
	 *
	 * @return void
	 */
	public function delete() {
		delete_site_option( $this->table );
	}

	/**
	 * Hook method called after the data is loaded.
	 *
	 * @return void
	 */
	protected function after_load(): void {
	}

	/**
	 * Hook method called before the data is loaded.
	 *
	 * @return void
	 */
	protected function before_load(): void {
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table(): string {
		return $this->table;
	}

	/**
	 * Get old setting values.
	 *
	 * @return array
	 */
	public function get_old_settings(): array {
		return $this->old_settings;
	}
}