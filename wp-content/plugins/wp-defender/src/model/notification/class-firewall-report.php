<?php
/**
 * Handles the scheduling and sending of firewall-related reports.
 *
 * @package WP_Defender\Model\Notification
 */

namespace WP_Defender\Model\Notification;

use WP_Defender\Component\Mail;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Controller\Firewall;
use WP_Defender\Component\Notification;

/**
 * Handles the scheduling and sending of firewall-related reports.
 */
class Firewall_Report extends \WP_Defender\Model\Notification {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'wd_lockout_report';

	public const SLUG = 'firewall-report';

	/**
	 * Constructor method.
	 * Sets default values for the firewall report.
	 */
	protected function before_load(): void {
		$default = array(
			'slug'                 => self::SLUG,
			'title'                => esc_html__( 'Firewall - Reporting', 'wpdef' ),
			'status'               => self::STATUS_DISABLED,
			'description'          => esc_html__(
				'Configure Defender to automatically email you a lockout report for this website.',
				'wpdef'
			),
			// @since 3.0.0 Fix 'Guest'-line.
			'in_house_recipients'  => is_user_logged_in() ? array( $this->get_default_user() ) : array(),
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

	/**
	 * Sends the firewall report email.
	 * Constructs and sends the firewall report email to the recipients.
	 *
	 * @return void
	 */
	public function send() {
		$service = wd_di()->get( Notification::class );
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $service );
		}
		foreach ( $this->out_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['name'], $recipient['email'], $service );
		}
		$this->last_sent     = $this->est_timestamp;
		$this->est_timestamp = $this->get_next_run()->getTimestamp();
		$this->save();
	}

	/**
	 * Sends the firewall report email to a specific user.
	 * Constructs and sends the firewall report email to the specified recipient.
	 *
	 * @param  string $name  The recipient's name.
	 * @param  string $email  The recipient's email address.
	 * @param  object $service  The notification service object.
	 *
	 * @return void
	 */
	private function send_to_user( $name, $email, $service ) {
		$site_url     = network_site_url();
		$mail_object  = wd_di()->get( Mail::class );
		$plugin_label = $mail_object->get_sender_name( self::SLUG );
		$subject      = sprintf(
		/* translators: 1. Plugin label. 2. Site URL. */
			esc_html__( '%1$s Lockouts Report for %2$s', 'wpdef' ),
			$plugin_label,
			$site_url
		);
		// Frequency.
		if ( 'daily' === $this->frequency ) {
			$time_unit = esc_html__( 'in the past 24 hours', 'wpdef' );
			$interval  = '-24 hours';
		} elseif ( 'weekly' === $this->frequency ) {
			$time_unit = esc_html__( 'in the past week', 'wpdef' );
			$interval  = '-7 days';
		} else {
			$time_unit = esc_html__( 'in the month', 'wpdef' );
			$interval  = '-30 days';
		}
		// Number of lockouts.
		$count_lockouts = array(
			'404'   => Lockout_Log::count(
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
			'ua'    => Lockout_Log::count(
				strtotime( $interval ),
				time(),
				array(
					Lockout_Log::LOCKOUT_UA,
				)
			),
		);

		$firewall = wd_di()->get( Firewall::class );
		$logs_url = network_admin_url( 'admin.php?page=wdf-ip-lockout&view=logs' );
		// Need for activated Mask Login feature.
		$logs_url         = apply_filters( 'report_email_logs_link', $logs_url, $email );
		$content_body     = $firewall->render_partial(
			'email/firewall-report',
			array(
				'name'           => $name,
				'count_total'    => (int) $count_lockouts['404'] + (int) $count_lockouts['login'] + (int) $count_lockouts['ua'],
				'time_unit'      => $time_unit,
				'logs_url'       => $logs_url,
				'site_url'       => $site_url,
				'count_lockouts' => $count_lockouts,
			),
			false
		);
		$unsubscribe_link = $service->create_unsubscribe_url( $this->slug, $email );
		$content          = $firewall->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Firewall', 'wpdef' ),
				'content_body'     => $content_body,
				'unsubscribe_link' => $unsubscribe_link,
			),
			false
		);

		$headers = $mail_object->get_headers(
			defender_noreply_email( 'wd_lockout_noreply_email' ),
			self::SLUG
		);

		$ret = wp_mail( $email, $subject, $content, $headers );
		if ( $ret ) {
			$this->save_log( $email );
		}
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'report'             => esc_html__( 'Firewall - Reporting', 'wpdef' ),
			'day'                => esc_html__( 'Day of', 'wpdef' ),
			'day_n'              => esc_html__( 'Day of', 'wpdef' ),
			'report_time'        => esc_html__( 'Time of day', 'wpdef' ),
			'report_frequency'   => esc_html__( 'Frequency', 'wpdef' ),
			'report_subscribers' => esc_html__( 'Recipients', 'wpdef' ),
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