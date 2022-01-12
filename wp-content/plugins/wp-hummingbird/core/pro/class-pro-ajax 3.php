<?php
/**
 * Class Pro_AJAX is used to parse ajax actions for the PRO version of the plugin.
 *
 * @since 1.5.0
 * @package Hummingbird\Core\Pro
 */

namespace Hummingbird\Core\Pro;

use Exception;
use Hummingbird\Core\Pro\Modules\Reports;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pro_AJAX
 */
class Pro_AJAX {

	/**
	 * Pro_AJAX constructor.
	 */
	public function __construct() {
		// Confirmation emails.
		add_action( 'wp_ajax_wphb_pro_resend_confirmation', array( $this, 'resend_confirmation' ) );
		add_action( 'wp_ajax_wphb_pro_send_confirmation', array( $this, 'send_confirmation' ) );

		// Notifications.
		add_action( 'wp_ajax_wphb_pro_disable_notification', array( $this, 'disable' ) );
		add_action( 'wp_ajax_wphb_pro_enable_notification', array( $this, 'enable' ) );
		add_action( 'wp_ajax_wphb_pro_search_users', array( $this, 'search_users' ) );
		add_action( 'wp_ajax_wphb_pro_get_avatar', array( $this, 'get_avatar' ) );
	}

	/**
	 * Check and validate the AJAX request, return selected module settings on success.
	 *
	 * @since 3.1.1
	 *
	 * @param string $module  Module: performance, uptime.
	 * @param string $type    Type: reports, notifications.
	 *
	 * @return mixed|string
	 */
	private function check_ajax_requirements( $module, $type ) {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Current user cannot modify settings.', 'wphb' ),
				)
			);
		}

		if ( ! isset( $module ) || ! isset( $type ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error parsing request.', 'wphb' ),
				)
			);
		}

		$settings = Settings::get_setting( $type, $module );

		if ( ! $settings ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error fetching settings for selected module.', 'wphb' ),
				)
			);
		}

		return $settings;
	}

	/**
	 * Resend email confirmation for Uptime notifications.
	 *
	 * @since 2.3.0
	 */
	public function resend_confirmation() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		$name  = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );

		if ( ! $email ) {
			wp_send_json_error();
		}

		Utils::get_api()->uptime->resend_confirmation( $email );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s - recipient name */
					esc_html__( 'The email is sent to %s for subscription confirmation.', 'wphb' ),
					$name
				),
			)
		);
	}

	/**
	 * Send email confirmation for Uptime notifications.
	 *
	 * @since 3.1.1
	 */
	public function send_confirmation() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		$name  = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );

		if ( ! $email ) {
			wp_send_json_error();
		}

		$recipients = array(
			array(
				'name'  => $name,
				'email' => $email,
			),
		);

		$notifications = Settings::get_setting( 'notifications', 'uptime' );

		if ( ! empty( $notifications['recipients'] ) ) {
			$emails = wp_list_pluck( $notifications['recipients'], 'email' );
			if ( ! in_array( $email, $emails, true ) ) {
				$recipients = array_merge( $notifications['recipients'], $recipients );
			}
		}

		try {
			$response = Utils::get_api()->uptime->update_recipients( $recipients );

			$emails = wp_list_pluck( $response, 'email' );
			$key    = array_search( $email, $emails, true );

			wp_send_json_success(
				array(
					'subscribed' => $response[ $key ]->is_subscribed,
					'pending'    => $response[ $key ]->is_pending,
					'canResend'  => $response[ $key ]->is_can_resend_confirmation,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Disable report.
	 *
	 * @since 3.1.1
	 *
	 * @param array  $settings  Settings array.
	 * @param string $module    Module. Accepts: performance, uptime.
	 * @param string $type      Type. Accepts: reports, notifications.
	 */
	private function disable_report( $settings, $module, $type ) {
		$settings['enabled']    = false;
		$settings['recipients'] = array();

		if ( 'reports' === $type ) {
			$settings['frequency'] = 7;
			$settings['day']       = '';
			$settings['time']      = '';

			wp_clear_scheduled_hook( "wphb_{$module}_report" );
		} elseif ( 'notifications' === $type ) {
			$settings['threshold'] = 0;

			if ( 'uptime' === $module ) {
				try {
					Utils::get_api()->uptime->update_recipients( $settings['recipients'] );
				} catch ( Exception $e ) {
					wp_send_json_error(
						array(
							'message' => $e->getMessage(),
						)
					);
				}
			}
		}

		Settings::update_setting( $type, $settings, $module );
	}

	/**
	 * Disable notification.
	 *
	 * @since 3.1.1
	 */
	public function disable() {
		$module = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_STRING );
		$type   = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );

		// This will end the request on failure.
		$settings = $this->check_ajax_requirements( $module, $type );

		$this->disable_report( $settings, $module, $type );

		wp_send_json_success();
	}

	/**
	 * Activate selected notification after adding all the details.
	 *
	 * @since 3.1.1
	 */
	public function enable() {
		$data   = filter_input( INPUT_POST, 'settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$update = filter_input( INPUT_POST, 'update', FILTER_VALIDATE_BOOLEAN );

		$module = isset( $data['module'] ) ? sanitize_key( $data['module'] ) : '';
		$type   = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : '';

		// This will end the request on failure.
		$settings = $this->check_ajax_requirements( $module, $type );

		if ( ! isset( $data['recipients'] ) || empty( $data['recipients'] ) ) {
			$this->disable_report( $settings, $module, $type );

			wp_send_json_success(
				array(
					'code' => 'disabled',
				)
			);
		}

		$code = $update ? 'updated' : 'configured';

		$settings['enabled'] = true;

		if ( 'database' !== $module && ! Utils::get_module( $module )->is_active() ) {
			Utils::get_module( $module )->enable();
		}

		if ( 'reports' === $type ) {
			$settings['frequency'] = (int) $data['schedule']['frequency'];
			if ( 30 === $settings['frequency'] ) {
				$settings['day'] = (int) $data['schedule']['monthDay'];
			} elseif ( 7 === $settings['frequency'] ) {
				$settings['day'] = sanitize_text_field( $data['schedule']['weekDay'] );
			}
			$settings['time'] = sanitize_text_field( $data['schedule']['time'] );

			// Randomize the minutes, so we don't spam the API.
			$email_time       = explode( ':', $settings['time'] );
			$email_time[1]    = sprintf( '%02d', wp_rand( 0, 59 ) );
			$settings['time'] = implode( ':', $email_time );

			// Update data for performance reports.
			if ( 'performance' === $module ) {
				$settings['type']     = isset( $data['performance']['device'] ) ? sanitize_key( $data['performance']['device'] ) : 'mobile';
				$settings['metrics']  = isset( $data['performance']['metrics'] ) && 'true' === $data['performance']['metrics'];
				$settings['audits']   = isset( $data['performance']['audits'] ) && 'true' === $data['performance']['audits'];
				$settings['historic'] = isset( $data['performance']['fieldData'] ) && 'true' === $data['performance']['fieldData'];
			} elseif ( 'uptime' === $module ) {
				$settings['show_ping'] = isset( $data['uptime']['showPing'] ) && 'true' === $data['uptime']['showPing'];
			} elseif ( 'database' === $module ) {
				$settings['tables']['revisions']          = isset( $data['database']['revisions'] ) && 'true' === $data['database']['revisions'];
				$settings['tables']['drafts']             = isset( $data['database']['drafts'] ) && 'true' === $data['database']['drafts'];
				$settings['tables']['trash']              = isset( $data['database']['trash'] ) && 'true' === $data['database']['trash'];
				$settings['tables']['spam']               = isset( $data['database']['spam'] ) && 'true' === $data['database']['spam'];
				$settings['tables']['trash_comment']      = isset( $data['database']['trashComment'] ) && 'true' === $data['database']['trashComment'];
				$settings['tables']['expired_transients'] = isset( $data['database']['expiredTransients'] ) && 'true' === $data['database']['expiredTransients'];
				$settings['tables']['transients']         = isset( $data['database']['transients'] ) && 'true' === $data['database']['transients'];
			}

			// Clear last sent time.
			if ( isset( $settings['last_sent'] ) ) {
				$settings['last_sent'] = '';
			}
		} else {
			$settings['threshold'] = (int) $data['schedule']['threshold'];

			// We need to do this to convert "false" strings to actual boolean values.
			foreach ( $data['recipients'] as $id => $recipient ) {
				if ( ! isset( $recipient['is_pending'] ) ) {
					continue;
				}

				$data['recipients'][ $id ]['is_pending']                 = filter_var( $recipient['is_pending'], FILTER_VALIDATE_BOOLEAN );
				$data['recipients'][ $id ]['is_subscribed']              = filter_var( $recipient['is_subscribed'], FILTER_VALIDATE_BOOLEAN );
				$data['recipients'][ $id ]['is_can_resend_confirmation'] = filter_var( $recipient['is_can_resend_confirmation'], FILTER_VALIDATE_BOOLEAN );
			}

			if ( 'uptime' === $module ) {
				try {
					$response = Utils::get_api()->uptime->update_recipients( $data['recipients'] );

					if ( isset( $response ) && is_array( $response ) && ! is_wp_error( $response ) ) {
						$recipients = json_decode( wp_json_encode( $response ), true ); // Convert to array.

						foreach ( $recipients as $id => $recipient ) {
							$key = array_search( $recipient['email'], array_column( $data['recipients'], 'email' ), true );
							if ( false === $key ) {
								$user = get_user_by( 'email', $recipient['email'] );

								$recipients[ $id ]['id']   = false === $user ? 0 : $user->ID;
								$recipients[ $id ]['role'] = false === $user || empty( $user->roles ) ? '' : ucfirst( $user->roles[0] );
							} else {
								$recipients[ $id ] = wp_parse_args( $recipient, $data['recipients'][ $key ] );
							}
						}

						$data['recipients'] = $recipients;
					}
				} catch ( Exception $e ) {
					wp_send_json_error(
						array(
							'message' => $e->getMessage(),
						)
					);
				}
			}
		}

		$settings['recipients'] = $data['recipients'];

		Settings::update_setting( $type, $settings, $module );

		// We need to do this at the end, because the settings need to be saved first.
		if ( 'reports' === $type && true === (bool) $settings['enabled'] ) {
			// Reschedule. No need to clear again, as we've just cleared on top.
			$next_scan_time = Reports::get_scheduled_time( $module );
			wp_schedule_single_event( $next_scan_time, "wphb_{$module}_report" );
		}

		wp_send_json_success(
			array(
				'code' => $code,
			)
		);
	}

	/**
	 * Search users from the add recipients modal.
	 *
	 * @since 3.1.1
	 */
	public function search_users() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$query = "*$query*";

		$exclude = filter_input( INPUT_POST, 'exclude', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$users = Utils::pro()->module( 'notifications' )->get_users( $query, $exclude );

		wp_send_json_success( $users );
	}

	/**
	 * Get avatar based on email.
	 *
	 * @since 3.1.1
	 */
	public function get_avatar() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		$email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );

		if ( false === $email ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid email.', 'wphb' ),
				)
			);
		}

		wp_send_json_success( get_avatar_url( $email ) );
	}

}