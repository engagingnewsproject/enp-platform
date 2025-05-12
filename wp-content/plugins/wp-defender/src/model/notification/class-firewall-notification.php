<?php
/**
 * Handles the scheduling and sending of firewall-related notifications.
 *
 * @package WP_Defender\Model\Notification
 */

namespace WP_Defender\Model\Notification;

use WP_Defender\Traits\IO;
use WP_Defender\Component\Mail;
use WP_Defender\Component\Two_Fa;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Email_Track;
use WP_Defender\Component\Notification;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Controller\Blocklist_Monitor;
use WP_Defender\Model\Setting\Notfound_Lockout;

/**
 * Handles the scheduling and sending of firewall-related notifications.
 */
class Firewall_Notification extends \WP_Defender\Model\Notification {

	use IO;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'wd_malware_firewall_notification';

	/**
	 * Slug identifier for the firewall notification.
	 *
	 * @var string
	 */
	public const SLUG = 'firewall-notification';

	/**
	 * Constructor method.
	 * Sets default values for the class.
	 */
	protected function before_load(): void {
		$default = array(
			'title'                => esc_html__( 'Firewall - Notification', 'wpdef' ),
			'slug'                 => self::SLUG,
			'status'               => self::STATUS_DISABLED,
			'description'          => esc_html__(
				'Get email when a user or IP is locked out for trying to access your login area.',
				'wpdef'
			),
			// @since 3.0.0 Fix 'Guest'-line.
			'in_house_recipients'  => is_user_logged_in() ? array( $this->get_default_user() ) : array(),
			'out_house_recipients' => array(),
			'type'                 => 'notification',
			'dry_run'              => false,
			'configs'              => array(
				'login_lockout' => false,
				'nf_lockout'    => false,
				'ua_lockout'    => false,
				'limit'         => false,
				'threshold'     => 3,
				'cool_off'      => 24,
			),
		);
		$this->import( $default );
	}

	/**
	 * Checks whether the notification options are enabled for the specified lockout log.
	 *
	 * @param  Lockout_Log $model  The lockout log model.
	 *
	 * @return bool True if notification options are enabled; otherwise, false.
	 */
	public function check_options( Lockout_Log $model ): bool {
		if ( self::STATUS_ACTIVE !== $this->status ) {
			return false;
		}
		// Check 'Login Protection Lockout'.
		if ( Lockout_Log::AUTH_LOCK === $model->type && true === $this->configs['login_lockout'] ) {
			return true;
		}
		// Check '404 Protection Lockout'.
		if ( Lockout_Log::LOCKOUT_404 === $model->type && true === $this->configs['nf_lockout'] ) {
			return true;
		}
		// Check 'User Agent Lockout'.
		if ( Lockout_Log::LOCKOUT_UA === $model->type && true === $this->configs['ua_lockout'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Constructs and sends the firewall notification email based on the lockout log.
	 *
	 * @param  Lockout_Log $model  The lockout log model.
	 *
	 * @return void
	 */
	public function send( Lockout_Log $model ): void {
		if (
			true === filter_var( $this->configs['login_lockout'], FILTER_VALIDATE_BOOLEAN )
			&& Lockout_Log::AUTH_LOCK === $model->type
		) {
			$template = 'login-lockout';
		} elseif (
			true === filter_var( $this->configs['ua_lockout'], FILTER_VALIDATE_BOOLEAN )
			&& Lockout_Log::LOCKOUT_UA === $model->type
		) {
			$template = 'ua-lockout';
		} else {
			$template = 'lockout-404';
		}

		$service = wd_di()->get( Notification::class );
		foreach ( $this->in_house_recipients as $user ) {
			if ( self::USER_SUBSCRIBED !== $user['status'] ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template, $service );
		}

		foreach ( $this->out_house_recipients as $user ) {
			if ( self::USER_SUBSCRIBED !== $user['status'] ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template, $service );
		}
	}

	/**
	 * Constructs and sends the email to the specified recipient.
	 *
	 * @param  string      $email  The recipient's email address.
	 * @param  string      $name  The recipient's name.
	 * @param  Lockout_Log $model  The lockout log model.
	 * @param  string      $template  The email template to use.
	 * @param  object      $service  The notification service object.
	 *
	 * @return void
	 */
	private function send_to_user(
		string $email,
		string $name,
		Lockout_Log $model,
		string $template,
		object $service
	): void {
		// Check if this meet the threshold.
		if ( true === $this->configs['limit'] ) {
			$count = Email_Track::count(
				$this->slug,
				$email,
				strtotime( '-' . $this->configs['cool_off'] . ' hours' ),
				time()
			);
			if ( $count >= $this->configs['threshold'] ) {
				// No send.
				return;
			}
		}
		$network_site_url = network_site_url();
		if ( 'login-lockout' === $template ) {
			/* translators: %s: Site URL. */
			$subject = sprintf( esc_html__( 'Login lockout alert for %s', 'wpdef' ), $network_site_url );
			// If the log is made from the 2FA module, then we get the settings from it, otherwise from Login_Lockout.
			$settings = wd_di()->get( Login_Lockout::class );
			if ( false !== strpos( $model->log, '2fa attempts' ) ) {
				$component     = wd_di()->get( Two_Fa::class );
				$attempt_limit = $component->get_attempt_limit();
				$time_limit    = $component->get_time_limit() . esc_html__( ' seconds', 'wpdef' );
				$type          = '2fa';
			} else {
				$attempt_limit = $settings->attempt;
				$time_limit    = $settings->duration . ' ' . $settings->duration_unit;
				$type          = 'login';
			}
			// $text & $string will be escaped at src\view\email\login-lockout.php.
			/* translators: 1: IP address, 2: Site URL, 3: Total attempt from an IP, 4. Lockout type, 5: Translation string. */
			$text = __(
				'The host %1$s has been locked out of %2$s due to more than %3$s failed %4$s attempts. %5$s',
				'wpdef'
			);
			if ( 'permanent' === $settings->lockout_type ) {
				$string = esc_html__( 'Accordingly, the host has been permanently banned.', 'wpdef' );
			} else {
				$string = sprintf(
				/* translators: %s: Duration. */
					esc_html__( 'They have been locked out for %s.', 'wpdef' ),
					'<strong>' . esc_html( $time_limit ) . '</strong>'
				);
			}
			$text = sprintf(
				$text,
				'<strong>' . $model->ip . '</strong>',
				'<a href="' . $network_site_url . '">' . $network_site_url . '</a>',
				'<strong>' . $attempt_limit . '</strong>',
				$type,
				$string
			);
		} elseif ( 'ua-lockout' === $template ) {
			$subject = sprintf(
			/* translators: %s: Site URL. */
				esc_html__( 'User Agent lockout alert for %s', 'wpdef' ),
				$network_site_url
			);
			$text = sprintf(
			/* translators: 1: User agent, 2: Site URL */
				__( 'The %1$s has been locked out of %2$s.', 'wpdef' ),
				'<strong>' . $model->user_agent . '</strong>',
				'<a href="' . $network_site_url . '">' . $network_site_url . '</a>',
			);
		} else {
			/* translators: %s: Site URL. */
			$subject  = sprintf( esc_html__( '404 lockout alert for %s', 'wpdef' ), $network_site_url );
			$settings = wd_di()->get( Notfound_Lockout::class );
			/* translators: 1: IP address, 2: Site URL, 3: Total attempt from an IP, 4: Tried, 5. Translation string. */
			$text = __(
				'The host %1$s has been locked out of %2$s due to more than %3$s 404 requests for the file %4$s. %5$s',
				'wpdef'
			);
			if ( 'permanent' === $settings->lockout_type ) {
				$string = esc_html__( 'Accordingly, the host has been permanently banned.', 'wpdef' );
			} else {
				$string = sprintf(
					/* translators: %s: Duration. */
					__( 'They have been locked out for %s.', 'wpdef' ),
					'<strong>' . esc_html( $settings->duration . ' ' . $settings->duration_unit ) . '</strong>'
				);
			}
			$text = sprintf(
				$text,
				'<strong>' . $model->ip . '</strong>',
				'<a href="' . $network_site_url . '">' . $network_site_url . '</a>',
				'<strong>' . $settings->attempt . '</strong>',
				'<strong>' . $model->tried . '</strong>',
				$string
			);
		}

		$logs_url = network_admin_url( 'admin.php?page=wdf-ip-lockout&view=logs' );
		// Need for activated Mask Login feature.
		$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );
		// We don't call the Firewall controller to avoid cyclic dependency. It's a workaround with the simplest controller.
		$controller       = wd_di()->get( Blocklist_Monitor::class );
		$content_body     = $controller->render_partial(
			'email/login-lockout',
			array(
				'name'     => $name,
				// It's escaped value.
				'text'     => $text,
				'logs_url' => $logs_url,
			),
			false
		);
		$unsubscribe_link = $service->create_unsubscribe_url( $this->slug, $email );
		$content          = $controller->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Firewall', 'wpdef' ),
				'content_body'     => $content_body,
				'unsubscribe_link' => $unsubscribe_link,
			),
			false
		);

		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_lockout_noreply_email' ),
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
	 * @return array The array of settings labels.
	 */
	public function labels(): array {
		return array(
			'notification'               => esc_html__( 'Firewall - Notification', 'wpdef' ),
			'login_lockout_notification' => esc_html__( 'Login Protection Lockout', 'wpdef' ),
			'ip_lockout_notification'    => esc_html__( '404 Detection Lockout', 'wpdef' ),
			'ua_lockout_notification'    => esc_html__( 'User Agent Lockout', 'wpdef' ),
			'notification_subscribers'   => esc_html__( 'Recipients', 'wpdef' ),
			'cooldown_enabled'           => esc_html__( 'Limit email notifications for repeat lockouts', 'wpdef' ),
			'cooldown_number_lockout'    => esc_html__( 'Repeat Lockouts Threshold', 'wpdef' ),
			'cooldown_period'            => esc_html__( 'Repeat Lockouts Period', 'wpdef' ),
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
	public function type_casting( array $configs ): array {
		$configs['login_lockout'] = (bool) $configs['login_lockout'];
		$configs['nf_lockout']    = (bool) $configs['nf_lockout'];
		$configs['ua_lockout']    = isset( $configs['ua_lockout'] ) ? (bool) $configs['ua_lockout'] : false;
		$configs['limit']         = (bool) $configs['limit'];

		return $configs;
	}
}