<?php
/**
 * Handles the scheduling and sending of audit logging reports.
 *
 * @package WP_Defender\Model\Notification
 */

namespace WP_Defender\Model\Notification;

use DateTime;
use Exception;
use WP_Defender\Component\Mail;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Component\Notification;
use WP_Defender\Controller\Audit_Logging;

/**
 * Model for audit logging settings.
 */
class Audit_Report extends \WP_Defender\Model\Notification {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'wd_audit_report';
	/**
	 * Slug identifier for the audit report.
	 *
	 * @var string
	 */
	public const SLUG = 'audit-report';

	/**
	 * Constructor method.
	 * Sets default values for the class.
	 */
	protected function before_load(): void {
		$default = array(
			'title'                => esc_html__( 'Audit Logging - Reporting', 'wpdef' ),
			'slug'                 => self::SLUG,
			'status'               => self::STATUS_DISABLED,
			'description'          => esc_html__(
				'Schedule Defender to automatically email you a summary of all your website events.',
				'wpdef'
			),
			// @since 3.0.0 Fix 'Guest'-line.
			'in_house_recipients'  => is_user_logged_in() ? array( $this->get_default_user() ) : array(),
			'out_house_recipients' => array(),
			'type'                 => 'report',
			'frequency'            => 'weekly',
			'day'                  => 'sunday',
			'time'                 => '4:00',
			'day_n'                => '1',
			'dry_run'              => false,
			'configs'              => array(),
		);
		$this->import( $default );
	}

	/**
	 * Sends the email.
	 * Retrieves data based on logic.
	 *
	 * @return void
	 * @throws Exception When an error occurs.
	 */
	public function send() {
		$date_to = new DateTime( 'now', wp_timezone() );
		switch ( $this->frequency ) {
			case 'daily':
				$date_from = new DateTime( '-24 hours', wp_timezone() );
				break;
			case 'weekly':
				$date_from = new DateTime( '-7 days', wp_timezone() );
				break;
			case 'monthly':
			default:
				$date_from = new DateTime( '-30 days', wp_timezone() );
				break;
		}
		$data = Audit_Log::query( $date_from->getTimestamp(), $date_to->getTimestamp(), array(), '', '', false );

		$collection = array();
		// Collect data.
		foreach ( $data as $item ) {
			if ( ! isset( $collection[ $item->event_type ][ $item->action_type ] ) ) {
				$collection[ $item->event_type ][ $item->action_type ] = 0;
			}
			++$collection[ $item->event_type ][ $item->action_type ];
		}
		$service = wd_di()->get( Notification::class );
		// Send data.
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $data, $collection, $service );
		}
		foreach ( $this->out_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $data, $collection, $service );
		}
		// Last sent should be the previous timestamp.
		$this->last_sent     = $this->est_timestamp;
		$this->est_timestamp = $this->get_next_run()->getTimestamp();
		$this->save();
	}

	/**
	 * Constructs and sends the email to the specified recipient.
	 *
	 * @param string $name    The recipient's name.
	 * @param string $email   The recipient's email address.
	 * @param array  $data    The audit log data.
	 * @param array  $collection    The summarized list of events.
	 * @param object $service The notification service object.
	 */
	private function send_to_user( $name, $email, $data, $collection, $service ) {
		$site_url = network_site_url();
		// Use smart quotes to display the email subject in HTML correctly and save escaping.
		$subject = sprintf(
		/* translators: %s: Site URL. */
			esc_html__( 'Here`s what`s been happening at %s', 'wpdef' ),
			$site_url
		);
		$audit_logging = wd_di()->get( Audit_Logging::class );
		$mail_object   = wd_di()->get( Mail::class );
		$plugin_label  = $mail_object->get_sender_name( self::SLUG );
		if ( count( $data ) ) {
			$logs_url = network_admin_url( 'admin.php?page=wdf-logging&view=logs' );
			// Need for activated Mask Login feature.
			$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );
			$header   = sprintf(
			/* translators: 1. Plugin label. 2. Site URL. */
				esc_html__( 'Audit Update From %1$s! %2$s', 'wpdef' ),
				$plugin_label,
				$site_url
			);
			$message = $audit_logging->render_partial(
				'email/audit-report-table',
				array(
					'logs_url'   => $logs_url,
					'name'       => $name,
					'collection' => $collection,
					'header'     => $header,
				),
				false
			);
		} else {
			$message = $audit_logging->render_partial(
				'email/audit-report-no-events',
				array(
					'name'     => $name,
					'site_url' => $site_url,
				),
				false
			);
		}
		$unsubscribe_link = $service->create_unsubscribe_url( $this->slug, $email );
		// Main email template.
		$content = $audit_logging->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Audit Logging', 'wpdef' ),
				'content_body'     => $message,
				'unsubscribe_link' => $unsubscribe_link,
			),
			false
		);

		$headers = $mail_object->get_headers(
			defender_noreply_email( 'wd_audit_noreply_email' ),
			self::SLUG
		);

		$ret = wp_mail( $email, $subject, $content, $headers );
		if ( $ret ) {
			$this->save_log( $email );
		}
	}

	/**
	 * Define labels.
	 *
	 * @return array The array of labels.
	 */
	public function labels(): array {
		return array(
			'report'      => esc_html__( 'Audit Logging - Reporting', 'wpdef' ),
			'subscribers' => esc_html__( 'Recipients', 'wpdef' ),
			'day'         => esc_html__( 'Day of', 'wpdef' ),
			'day_n'       => esc_html__( 'Day of', 'wpdef' ),
			'time'        => esc_html__( 'Time of day', 'wpdef' ),
			'frequency'   => esc_html__( 'Frequency', 'wpdef' ),
		);
	}

	/**
	 * Additional converting rules.
	 *
	 * @param  array $configs  The configuration data.
	 *
	 * @return array The type-casted configuration data.
	 * @since 3.1.0
	 */
	public function type_casting( $configs ): array {
		return is_array( $configs ) ? $configs : array();
	}
}