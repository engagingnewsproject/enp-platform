<?php

namespace WP_Defender\Model\Notification;

use WP_Defender\Controller\Firewall;
use WP_Defender\Model\Lockout_Log;

/**
 * Class Firewall_Report.
 * @package WP_Defender\Model\Notification
 */
class Firewall_Report extends \WP_Defender\Model\Notification {
	protected $table = 'wd_lockout_report';

	public function before_load() {
		$default = array(
			'slug'                 => 'firewall-report',
			'title'                => __( 'Firewall - Reporting', 'wpdef' ),
			'status'               => self::STATUS_DISABLED,
			'description'          => __( 'Configure Defender to automatically email you a lockout report for this website.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
			'out_house_recipients' => array(),
			'type'                 => 'report',
			'dry_run'              => false,
			'frequency'            => 'weekly',
			'day'                  => 'sunday',
			'day_n'                => '1',
			'time'                 => '4:00',
			'configs'              => array(),
		);
		$this->import( $default );
	}

	public function send() {
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'] );
		}
		foreach ( $this->out_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'] );
		}
		$this->last_sent     = $this->est_timestamp;
		$this->est_timestamp = $this->get_next_run()->getTimestamp();
		$this->save();
	}

	private function send_to_user( $name, $email ) {
		$site_url = network_site_url();
		/* translators: */
		$subject = sprintf( __( 'Defender Lockouts Report for %s', 'wpdef' ), $site_url );
		if ( 'daily' === $this->frequency ) {
			$time_unit = __( 'in the past 24 hours', 'wpdef' );
			$interval  = '-24 hours';
		} elseif ( 'weekly' === $this->frequency ) {
			$time_unit = __( 'in the past week', 'wpdef' );
			$interval  = '-7 days';
		} else {
			$time_unit = __( 'in the month', 'wpdef' );
			$interval  = '-30 days';
		}
		// Number of lockouts.
		$count_lockouts = array(
			'404'  => Lockout_Log::count(
				strtotime( $interval ),
				time(),
				array(
					Lockout_Log::LOCKOUT_404,
				)
			),
			'login' => Lockout_Log::count(
				strtotime( $interval ),
				time(),
				array(
					Lockout_Log::AUTH_LOCK,
				)
			),
			// Todo: add UA-lockouts when UA-checkbox appears in the Firewall email configure.
			// Todo: change COUNT-logic for all lockouts when UA-checkbox will be ready, e.g. Lockout_Log::count_lockout_in_24_hours().
		);

		$firewall     = wd_di()->get( Firewall::class );
		$logs_url     = network_admin_url( 'admin.php?page=wdf-ip-lockout&view=logs' );
		// Need for activated Mask Login feature.
		$logs_url     = apply_filters( 'report_email_logs_link', $logs_url, $email );
		$content_body = $firewall->render_partial(
			'email/firewall-report',
			array(
				'name'           => $name,
				'count_total'    => (int) $count_lockouts['404'] + (int) $count_lockouts['login'],
				'time_unit'      => $time_unit,
				'logs_url'       => $logs_url,
				'site_url'       => $site_url,
				'count_lockouts' => $count_lockouts,
			),
			false
		);
		$content      = $firewall->render_partial(
			'email/index',
			array(
				'title'        => __( 'Firewall', 'wpdef' ),
				'content_body' => $content_body,
			),
			false
		);

		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_lockout_noreply_email' )
		);

		$ret = wp_mail( $email, $subject, $content, $headers );
		if ( $ret ) {
			$this->save_log( $email );
		}
	}

	/**
	 * Define labels for settings key.
	 *
	 * @param string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'report'             => __( 'Firewall - Reporting', 'wpdef' ),
			'day'                => __( 'Day of', 'wpdef' ),
			'day_n'              => __( 'Day of', 'wpdef' ),
			'report_time'        => __( 'Time of day', 'wpdef' ),
			'report_frequency'   => __( 'Frequency', 'wpdef' ),
			'report_subscribers' => __( 'Recipients', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
