<?php
/**
 * Class to handle mixpanel events functionality.
 *
 * @since   4.2.0
 * @package WP_Defender
 */

namespace WP_Defender;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Model\Setting\Main_Setting;
use WP_Defender\Component\Product_Analytics;
use WP_Defender\Traits\Array_Utils;

/**
 * Abstract class for Mixpanel Events.
 */
abstract class Event extends Controller {

	use Array_Utils;

	/**
	 * Location of the event
	 *
	 * @var string
	 */
	protected $location = '';

	/**
	 * Get mixpanel instance.
	 */
	private function tracker() {
		return wd_di()->get( Product_Analytics::class )->get_mixpanel();
	}

	/**
	 * Check if usage tracking is active.
	 *
	 * @return bool
	 */
	protected function is_tracking_active() {
		return wd_di()->get( Main_Setting::class )->usage_tracking;
	}

	/**
	 * Check if the current moment is right for tracking.
	 *
	 * @return bool
	 */
	protected function maybe_track(): bool {
		return ! defender_is_wp_cli() && $this->is_tracking_active();
	}

	/**
	 * Has the data changed?
	 *
	 * @param  array $old_data  Old data to compare.
	 * @param  array $new_data  New data to compare.
	 *
	 * @return bool
	 */
	protected function is_feature_state_changed( $old_data, $new_data ) {
		// Handle arrays with nested arrays or objects by using deep comparison.
		return $this->arrays_differ_deeply( $old_data, $new_data );
	}

	/**
	 * Track data tracking opt in and opt out.
	 *
	 * @param  bool   $active  Toggle value.
	 * @param  string $from  Triggered method.
	 *
	 * @return void
	 */
	protected function track_opt_toggle( $active, $from ) {
		$this->tracker()->track(
			$active ? 'Opt In' : 'Opt Out',
			array(
				'Method' => $from,
			)
		);
	}

	/**
	 * Tracks a feature event if tracking is active.
	 *
	 * @param  mixed $event  The event to track.
	 * @param  array $data  The data associated with the event.
	 *
	 * @return void
	 */
	public function track_feature( $event, $data ) {
		if ( $this->is_tracking_active() ) {
			$this->tracker()->track( $event, $data );
		}
	}

	/**
	 * Save tracking state.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function track_feature_handler( Request $request ): Response {
		// Forced tracking.
		$data = $request->get_data();
		if ( isset( $data['force'] ) ) {
			$this->tracker()->track( $data['event'], $data['data'] );

			return new Response( true, array() );
		}
		// Otherwise it's a normal process.
		if ( $this->is_tracking_active() ) {
			$this->track_feature( $data['event'], $data['data'] );
		}

		return new Response( true, array() );
	}

	/**
	 * Sets the intention of the object to the specified location.
	 *
	 * @param  string $location  The location to set as the intention.
	 */
	public function set_intention( string $location ) {
		$this->location = $location;
	}

	/**
	 * Retrieves the location where the event was triggered.
	 *
	 * @return string The location where the event was triggered.
	 */
	public function get_triggered_location() {
		return $this->location;
	}
}