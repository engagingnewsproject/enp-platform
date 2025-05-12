<?php
/**
 * Handles the notification of security recommendations that need fixing.
 *
 * @package WP_Defender\Model\Notification
 */

namespace WP_Defender\Model\Notification;

use DateTime;
use Countable;
use Exception;
use DateInterval;
use DI\DependencyException;
use Calotes\Helper\Array_Cache;
use WP_Defender\Component\Mail;
use WP_Defender\Component\Notification;
use WP_Defender\Controller\Security_Tweaks;

/**
 * Handles the notification of security recommendations that need fixing.
 */
class Tweak_Reminder extends \WP_Defender\Model\Notification {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'wd_security_tweaks_reminder';
	/**
	 * Slug identifier for the tweak reminder.
	 *
	 * @var string
	 */
	public const SLUG = 'tweak-reminder';

	/**
	 * Constructor method.
	 * Sets default values for the class.
	 */
	protected function before_load(): void {
		$params = array(
			'slug'                 => self::SLUG,
			'title'                => esc_html__( 'Security Recommendations - Notification', 'wpdef' ),
			'status'               => self::STATUS_DISABLED,
			'description'          => esc_html__(
				'Get email notifications if/when a security recommendation needs fixing.',
				'wpdef'
			),
			// @since 3.0.0 Fix 'Guest'-line.
			'in_house_recipients'  => is_user_logged_in() ? array( $this->get_default_user() ) : array(),
			'out_house_recipients' => array(),
			'type'                 => 'notification',
			'dry_run'              => false,
			'configs'              => array(
				'reminder' => 'weekly',
			),
		);
		$this->import( $params );
	}

	/**
	 * Determines if a notification should be sent based on the current status and time.
	 *
	 * @return bool Returns true if a notification should be sent, false otherwise.
	 */
	public function maybe_send(): bool {
		if ( self::STATUS_ACTIVE !== $this->status ) {
			return false;
		}

		$est = new DateTime( 'now', wp_timezone() );
		$est->setTimestamp( $this->last_sent );
		$now      = new DateTime( 'now', wp_timezone() );
		$interval = DateInterval::createFromDateString( (string) $est->getOffset() . 'seconds' );
		$now->add( $interval );

		switch ( $this->configs['reminder'] ) {
			case 'daily':
				$est->add( new DateInterval( 'P1D' ) );
				break;
			case 'weekly':
				$est->add( new DateInterval( 'P1W' ) );
				break;
			case 'monthly':
			default:
				$est->add( new DateInterval( 'P1M' ) );
				break;
		}
		$est->add( $interval );
		// Testing.
		if ( defined( 'WP_DEFENDER_TESTING' ) && true === constant( 'WP_DEFENDER_TESTING' ) ) {
			return true;
		}
		if ( $est->getTimestamp() < $now->getTimestamp() ) {
			return true;
		}

		return false;
	}

	/**
	 * Sends the email.
	 * Retrieves data based on logic.
	 *
	 * @return void
	 * @throws Exception When an error occurs.
	 */
	public function send() {
		$tweaks = wd_di()->get( \WP_Defender\Model\Setting\Security_Tweaks::class );
		if ( 0 === ( is_array( $tweaks->issues ) || $tweaks->issues instanceof Countable ? count( $tweaks->issues ) : 0 ) ) {
			return;
		}
		$arr        = Array_Cache::get( 'tweaks', 'tweaks' );
		$issues     = '';
		$template   = wd_di()->get( Security_Tweaks::class );
		$status_img = defender_asset_url( '/assets/email-assets/img/Warning@2x.png' );
		foreach ( $tweaks->issues as $slug ) {
			if ( isset( $arr[ $slug ] ) ) {
				$issue   = $arr[ $slug ];
				$issues .= $template->render_partial(
					'email/tweak-issue',
					array(
						'tweak_title'  => $issue->get_label(),
						'error_reason' => $issue->get_error_reason(),
						'status_img'   => $status_img,
					),
					false
				);
			}
		}

		$service = wd_di()->get( Notification::class );
		foreach ( $this->in_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['email'], $recipient['name'], $issues, $service );
		}

		foreach ( $this->out_house_recipients as $recipient ) {
			if ( self::USER_SUBSCRIBED !== $recipient['status'] ) {
				continue;
			}
			$this->send_to_user( $recipient['email'], $recipient['name'], $issues, $service );
		}
		$this->last_sent = time();
		$this->save();
	}

	/**
	 * Sends the email to a specific user.
	 * Constructs and sends the email to the specified recipient.
	 *
	 * @param  string $email  The recipient's email address.
	 * @param  string $name  The recipient's name.
	 * @param  array  $issues  The summarized list of events.
	 * @param  object $service  The notification service object.
	 *
	 * @throws DependencyException If a dependency cannot be resolved.
	 * @return void
	 */
	public function send_to_user( $email, $name, $issues, $service ) {
		$tweaks   = wd_di()->get( \WP_Defender\Model\Setting\Security_Tweaks::class );
		$logs_url = network_admin_url( 'admin.php?page=wdf-hardener' );
		$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );

		$security_tweak   = wd_di()->get( Security_Tweaks::class );
		$content_body     = $security_tweak->render_partial(
			'email/tweaks-reminder',
			array(
				'count'    => is_array( $tweaks->issues ) || $tweaks->issues instanceof Countable ? count( $tweaks->issues ) : 0,
				'view_url' => $logs_url,
				'name'     => $name,
				'issues'   => $issues,
				'site_url' => network_site_url(),
			),
			false
		);
		$unsubscribe_link = $service->create_unsubscribe_url( $this->slug, $email );
		$content          = $security_tweak->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Security Report', 'wpdef' ),
				'content_body'     => $content_body,
				'unsubscribe_link' => $unsubscribe_link,
			),
			false
		);

		/* translators: 1: Site URL, 2: Count for unresolved security recommendations */
		$subject = _n(
			'Security Recommendation Report for %1$s. %2$s recommendation to action.',
			'Security Recommendation Report for %1$s. %2$s recommendations to action.',
			is_array( $tweaks->issues ) || $tweaks->issues instanceof Countable ? count( $tweaks->issues ) : 0,
			'wpdef'
		);
		$subject = sprintf(
			$subject,
			network_site_url(),
			is_array( $tweaks->issues ) || $tweaks->issues instanceof Countable ? count( $tweaks->issues ) : 0
		);

		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_recommendation_noreply_email' ),
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
			'notification'        => esc_html__( 'Security Recommendations - Notification', 'wpdef' ),
			'notification_repeat' => esc_html__( 'Frequency', 'wpdef' ),
			'subscribers'         => esc_html__( 'Recipients', 'wpdef' ),
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