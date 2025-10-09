<?php
/**
 * Responsible for gathering analytics data for the AntiBot feature.
 *
 * @package WP_Defender\Helper\Analytics
 */

namespace WP_Defender\Helper\Analytics;

use WP_Defender\Event;
use WP_Defender\Traits\Defender_Dashboard_Client;
use WP_Defender\Component\IP\Antibot_Global_Firewall;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;

/**
 * Gather analytics data required for AntiBot feature.
 */
class Antibot extends Event {
	use Defender_Dashboard_Client;

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
	 * @param array $data Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Exports strings.
	 *
	 * @return array
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Track feature.
	 *
	 * @param bool   $enabled  Feature is enabled or not.
	 * @param string $location Options: Feature Page, Dashboard, Onboarding, Hub and Welcome-modal.
	 *
	 * @return void
	 */
	public function track_antibot( bool $enabled, string $location ) {
		$event = $enabled ? 'def_antibot_deactivated' : 'def_antibot_activated';
		$data  = array(
			'Triggered From' => $location,
			'State'          => 'plugin' === wd_di()->get( Antibot_Global_Firewall::class )->get_managed_by()
				? 'Managed by Defender Plugin'
				: 'Managed by WPMU DEV Hosting',
			'Mode'           => wd_di()->get( Antibot_Global_Firewall_Setting::class )->get_mode_label(),
		);
		if ( 'Hub' !== $location ) {
			$data['Connection Method'] = $this->is_dash_activated()
				? 'Dashboard Plugin'
				: 'Hub Connector';
		}

		$this->track_feature( $event, $data );
	}
}