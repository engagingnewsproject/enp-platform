<?php

namespace WP_Defender\Model\Notification;

use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Model\Audit_Log;

class Audit_Report extends \WP_Defender\Model\Notification {
	protected $table = 'wd_audit_report';

	public function before_load() {
		$default = array(
			'title'                => __( 'Audit Logging - Reporting', 'wpdef' ),
			'slug'                 => 'audit-report',
			'status'               => self::STATUS_DISABLED,
			'description'          => __( 'Schedule Defender to automatically email you a summary of all your website events.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
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
	 * @throws \Exception
	 */
	public function send() {
		$date_to = new \DateTime( 'now', wp_timezone() );
		switch ( $this->frequency ) {
			case 'daily':
				$date_from = new \DateTime( '-24 hours', wp_timezone() );
				break;
			case 'weekly':
				$date_from = new \DateTime( '-7 days', wp_timezone() );
				break;
			case 'monthly':
			default:
				$date_from = new \DateTime( '-30 days', wp_timezone() );
				break;
		}
		$data = Audit_Log::query( $date_from->getTimestamp(), $date_to->getTimestamp(), [], '', '', false );

		$list = [];
		// Collect data.
		foreach ( $data as $item ) {
			if ( ! isset( $list[ $item->event_type ][ $item->action_type ] ) ) {
				$list[ $item->event_type ][ $item->action_type ] = 0;
			}
			$list[ $item->event_type ][ $item->action_type ] ++;
		}
		// Send data.
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $data, $list );
		}
		foreach ( $this->out_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $data, $list );
		}
		// Last sent should be the previous timestamp.
		$this->last_sent     = $this->est_timestamp;
		$this->est_timestamp = $this->get_next_run()->getTimestamp();
		$this->save();
	}

	/**
	 * @param string $name
	 * @param string $email
	 * @param array  $data
	 * @param array  $list
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	private function send_to_user( $name, $email, $data, $list ) {
		$site_url       = network_site_url();
		$subject        = sprintf( __( "Here's what's been happening at %s", 'wpdef' ), $site_url );
		$audit_logging  = wd_di()->get( Audit_Logging::class );
		if ( count( $data ) ) {
			$logs_url = network_admin_url( 'admin.php?page=wdf-logging&view=logs' );
			// Need for activated Mask Login feature.
			$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );
			$message  = $audit_logging->render_partial( 'email/audit-report-table', [
				'logs_url' => $logs_url,
				'name'     => $name,
				'list'     => $list,
				'site_url' => $site_url
			], false );
		} else {
			$message = $audit_logging->render_partial( 'email/audit-report-no-events', [
				'name'     => $name,
				'site_url' => $site_url
			], false );
		}
		// Main email template.
		$content = $audit_logging->render_partial(
			'email/index',
			array(
				'title'         => __( 'Audit Logging', 'wpdef' ),
				'content_body'  => $message,
			),
			false
		);

		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_audit_noreply_email' )
		);

		$ret = wp_mail( $email, $subject, $content, $headers );
		if ( $ret ) {
			$this->save_log( $email );
		}
	}

	/**
	 * Define labels for settings key.
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'report'      => __( 'Audit Logging - Reporting', 'wpdef' ),
			'subscribers' => __( 'Recipients', 'wpdef' ),
			'day'         => __( 'Day of', 'wpdef' ),
			'day_n'       => __( 'Day of', 'wpdef' ),
			'time'        => __( 'Time of day', 'wpdef' ),
			'frequency'   => __( 'Frequency', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
