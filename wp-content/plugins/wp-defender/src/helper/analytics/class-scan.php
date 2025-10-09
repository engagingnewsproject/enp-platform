<?php
/**
 * Responsible for gathering analytics data for the scan feature.
 *
 * @package WP_Defender\Helper\Analytics
 */

namespace WP_Defender\Helper\Analytics;

use WP_Defender\Event;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Scan as Scan_Model;

/**
 * Gather analytics data required for scan feature.
 */
class Scan extends Event {

	public const EVENT_SCAN_FAILED        = 'def_scan_failed_new';
	public const EVENT_SCAN_FAILED_PROP   = 'Failure reason';
	public const EVENT_SCAN_FAILED_CANCEL = 'User Cancellation';
	public const EVENT_SCAN_FAILED_ERROR  = 'Error';

	// Mandatory empty methods.
	// Start.
	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array();
	}

	/**
	 * Converts the current state of the object to an array.
	 *
	 * @return array Returns an associative array of object properties.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Removes settings for all submodules.
	 *
	 * @return mixed
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 *
	 * @return mixed
	 */
	public function remove_data() {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}
	// End.

	/**
	 * Analytics data for scan completed event.
	 *
	 * @param  Scan_Model $scan_model  Scan model object.
	 *
	 * @return array{
	 *   'event' => string,
	 *   'data' => array
	 * }
	 */
	public function scan_completed( Scan_Model $scan_model ): array {
		$last_scan = $scan_model::get_last();
		if ( ! $last_scan instanceof Scan_Model ) {
			return array();
		}
		$scan_item_group_total = wd_di()->get( Scan_Item::class )
										->get_types_total( $last_scan->id, Scan_Item::STATUS_ACTIVE );

		$data = array();

		if ( isset( $scan_item_group_total['all'] ) ) {
			$data['Threats Count'] = $scan_item_group_total['all'];
		}

		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_INTEGRITY ] ) ) {
			$data['WP core issue count'] = $scan_item_group_total[ Scan_Item::TYPE_INTEGRITY ];
		}

		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_SUSPICIOUS ] ) ) {
			$data['Suspicious Code'] = $scan_item_group_total[ Scan_Item::TYPE_SUSPICIOUS ];
		}

		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_CHECK ] ) ) {
			$data['Plugin file modified'] = $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_CHECK ];
		}

		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_VULNERABILITY ] ) ) {
			$data['Vulnerability'] = $scan_item_group_total[ Scan_Item::TYPE_VULNERABILITY ];
		}

		$is_closed   = false;
		$is_outdated = false;
		$count       = 0;
		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_CLOSED ] ) ) {
			$is_closed = true;
			$count    += $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_CLOSED ];
		}
		if ( isset( $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_OUTDATED ] ) ) {
			$is_outdated = true;
			$count      += $scan_item_group_total[ Scan_Item::TYPE_PLUGIN_OUTDATED ];
		}
		if ( $is_closed || $is_outdated ) {
			$data['Outdated & Removed Plugins'] = $count;
		}

		return array(
			'event' => 'def_scan_completed_new',
			'data'  => $data,
		);
	}
}