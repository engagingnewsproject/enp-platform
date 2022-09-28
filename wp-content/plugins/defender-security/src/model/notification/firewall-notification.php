<?php
declare( strict_types=1 );

namespace WP_Defender\Model\Notification;

use WP_Defender\Controller\Firewall;
use WP_Defender\Model\Email_Track;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Traits\IO;

/**
 * Class Firewall_Notification
 * @package WP_Defender\Model\Notification
 */
class Firewall_Notification extends \WP_Defender\Model\Notification {
	use IO;

	public $table = 'wd_malware_firewall_notification';

	/**
	 * @return void
	 */
	protected function before_load(): void {
		$default = [
			'title' => __( 'Firewall - Notification', 'wpdef' ),
			'slug' => 'firewall-notification',
			'status' => self::STATUS_DISABLED,
			'description' => __( 'Get email when a user or IP is locked out for trying to access your login area.', 'wpdef' ),
			// @since 3.0.0 Fix 'Guest'-line.
			'in_house_recipients' => is_user_logged_in() ? [ $this->get_default_user() ] : [],
			'out_house_recipients' => [],
			'type' => 'notification',
			'dry_run' => false,
			'configs' => [
				'login_lockout' => false,
				'nf_lockout' => false,
				'limit' => false,
				'threshold' => 3,
				'cool_off' => 24,
			],
		];
		$this->import( $default );
	}

	/**
	 * @param Lockout_Log $model
	 *
	 * @return bool
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

		return false;
	}

	/**
	 * @param Lockout_Log $model
	 *
	 * @return void
	 */
	public function send( Lockout_Log $model ): void {
		// Todo: add UA-template when UA-checkbox appears in the Firewall email template.
		if (
			true === filter_var( $this->configs['login_lockout'], FILTER_VALIDATE_BOOLEAN )
			&& Lockout_Log::AUTH_LOCK === $model->type
		) {
			$template = 'login-lockout';
		} else {
			$template = 'lockout-404';
		}

		$service = wd_di()->get( \WP_Defender\Component\Notification::class );
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
	 * @param string      $email
	 * @param string      $name
	 * @param Lockout_Log $model
	 * @param string      $template
	 * @param object      $service
	 *
	 * @return void
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	private function send_to_user( string $email, string $name, Lockout_Log $model, string $template, object $service ): void {
		// Check if this meet the threshold.
		if ( true === $this->configs['limit'] ) {
			$count = Email_Track::count( $this->slug, $email, strtotime( '-' . $this->configs['cool_off'] . ' hours' ), time() );
			if ( $count >= $this->configs['threshold'] ) {
				// No send.
				return;
			}
		}
		$network_site_url = network_site_url();
		if ( 'login-lockout' === $template ) {
			/* translators: Site URL. */
			$subject = sprintf( __( 'Login lockout alert for %s', 'wpdef' ), $network_site_url );// phpcs:ignore
			$settings = wd_di()->get( Login_Lockout::class );
			/* translators: 1: IP address, 2: Site URL, 3: Site URL, 4: Total attempt from an IP, 5. Translation string. */
			$text = __( 'The host <strong>%1$s</strong> has been locked out of <a href="%2$s">%3$s</a> due to more than <strong>%4$s</strong> failed login attempts. %5$s', 'wpdef' );// phpcs:ignore
			if ( 'permanent' === $settings->lockout_type ) {
				$string = __( 'Accordingly, the host has been permanently banned.', 'wpdef' );
			} else {
				/* translators: 1: Duration, 2: Duration Unit. */
				$string = sprintf( __( 'They have been locked out for <strong>%1$s %2$s.</strong>', 'wpdef' ), $settings->duration, $settings->duration_unit );// phpcs:ignore
			}
			$text = sprintf(
				$text,
				$model->ip,
				$network_site_url,
				$network_site_url,
				$settings->attempt,
				$string
			);
		} else {
			/* translators: Site URL. */
			$subject = sprintf( __( '404 lockout alert for %s', 'wpdef' ), $network_site_url );// phpcs:ignore
			$settings = wd_di()->get( Notfound_Lockout::class );
			/* translators: 1: IP address, 2: Site URL, 3: Site URL, 4: Total attempt from an IP, 5. Tried, 6. Translation string. */
			$text = __( 'The host <strong>%1$s</strong> has been locked out of <a href="%2$s">%3$s</a> due to more than <strong>%4$s</strong> 404 requests for the file <strong>%5$s</strong>. %6$s', 'wpdef' );// phpcs:ignore
			if ( 'permanent' === $settings->lockout_type ) {
				$string = __( 'Accordingly, the host has been permanently banned.', 'wpdef' );
			} else {
				/* translators: 1: Duration, 2: Duration Unit. */
				$string = sprintf( __( 'They have been locked out for <strong>%1$s %2$s.</strong>', 'wpdef' ), $settings->duration, $settings->duration_unit );// phpcs:ignore
			}
			$text = sprintf(
				$text,
				$model->ip,
				$network_site_url,
				$network_site_url,
				$settings->attempt,
				$model->tried,
				$string
			);
		}
		$firewall = wd_di()->get( Firewall::class );
		$logs_url = network_admin_url( 'admin.php?page=wdf-ip-lockout&view=logs' );
		// Need for activated Mask Login feature.
		$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );
		$content_body = $firewall->render_partial(
			'email/' . $template,
			[
				'name' => $name,
				'text' => $text,
				'logs_url' => $logs_url,
			],
			false
		);
		$unsubscribe_link = $service->create_unsubscribe_url( $this->slug, $email );
		$content = $firewall->render_partial(
			'email/index',
			[
				'title' => __( 'Firewall', 'wpdef' ),
				'content_body' => $content_body,
				'unsubscribe_link' => $unsubscribe_link,
			],
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
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return [
			'notification' => __( 'Firewall - Notification', 'wpdef' ),
			'login_lockout_notification' => __( 'Login Protection Lockout', 'wpdef' ),
			'ip_lockout_notification' => __( '404 Detection Lockout', 'wpdef' ),
			'notification_subscribers' => __( 'Recipients', 'wpdef' ),
			'cooldown_enabled' => __( 'Limit email notifications for repeat lockouts', 'wpdef' ),
			'cooldown_number_lockout' => __( 'Repeat Lockouts Threshold', 'wpdef' ),
			'cooldown_period' => __( 'Repeat Lockouts Period', 'wpdef' ),
		];
	}

	/**
	 * Additional converting rules.
	 *
	 * @param array $configs
	 *
	 * @return array
	 * @since 3.1.0
	*/
	public function type_casting( array $configs ): array {
		$configs['login_lockout'] = (bool) $configs['login_lockout'];
		$configs['nf_lockout'] = (bool) $configs['nf_lockout'];
		$configs['limit'] = (bool) $configs['limit'];

		return $configs;
	}
}
