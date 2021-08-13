<?php

namespace Hummingbird\Core\Api\Service;

use Hummingbird\Core\Api\Exception;
use Hummingbird\Core\Api\Request\WPMUDEV;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Uptime
 */
class Uptime extends Service {

	/**
	 * API module name.
	 *
	 * @var string $name
	 */
	protected $name = 'uptime';

	/**
	 * API version.
	 *
	 * @var string $version
	 */
	private $version = 'v1';

	/**
	 * Uptime constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		$this->request = new WPMUDEV( $this );
	}

	/**
	 * Get API version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get Uptime data for a given segment of time
	 *
	 * @param string $time  day|week|month.
	 *
	 * @return mixed
	 */
	public function check( $time = 'day' ) {
		$this->request->set_timeout( 20 );
		return $this->request->get(
			'stats/' . $time,
			array(
				'domain' => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Check if Uptime is enabled remotely
	 *
	 * @return mixed|WP_Error
	 */
	public function is_enabled() {
		$this->request->set_timeout( 30 );
		$results = $this->request->get(
			'stats/week/',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);

		if ( is_wp_error( $results ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enable Uptime remotely
	 *
	 * @return mixed|WP_Error
	 */
	public function enable() {
		$this->request->set_timeout( 30 );
		$results = $this->request->post(
			'monitoring',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);

		if ( true !== $results ) {
			if ( is_wp_error( $results ) ) {
				return $results;
			}

			if ( isset( $results->code ) && isset( $results->message ) ) {
				return new WP_Error( 500, $results->message );
			}

			return new WP_Error( 500, __( 'Unknown Error', 'wphb' ) );
		}

		return $results;
	}

	/**
	 * Disable Uptime remotely
	 *
	 * @return mixed|WP_Error
	 */
	public function disable() {
		$this->request->set_timeout( 30 );
		$results = $this->request->delete(
			'monitoring',
			array(
				'domain' => $this->request->get_this_site(),
			)
		);

		if ( true !== $results ) {
			return new WP_Error( 500, __( 'Unknown Error', 'wphb' ) );
		}

		return $results;
	}

	/**
	 * Update recipients.
	 *
	 * Making a POST request to this endpoint will trigger a confirmation email for the selected recipient.
	 *
	 * @since 2.1.0
	 *
	 * @param array $recipients  Recipients array. Example: ['name'=>'name', 'email'=>'email@example.org'].
	 *
	 * @return array|WP_Error
	 */
	public function update_recipients( array $recipients = [] ) {
		$this->request->set_timeout( 30 );

		$results = $this->request->post(
			'monitoring/recipients',
			array(
				'domain'     => $this->request->get_this_site(),
				'recipients' => wp_json_encode( $recipients ),
			)
		);

		if ( isset( $results->code ) && isset( $results->message ) ) {
			return new WP_Error( 500, $results->message );
		}

		return $results;
	}

	/**
	 * Resend notifications.
	 *
	 * @since 2.3.0
	 *
	 * @param string $email  Email to re-send the confirmation for.
	 *
	 * @return array|WP_Error
	 */
	public function resend_confirmation( $email ) {
		$this->request->set_timeout( 30 );

		$results = $this->request->post(
			'monitoring/recipient/resend-confirmation',
			array(
				'domain'    => $this->request->get_this_site(),
				'recipient' => $email,
			)
		);

		if ( isset( $results->code ) && isset( $results->message ) ) {
			return new WP_Error( 500, $results->message );
		}

		return $results;
	}

}
