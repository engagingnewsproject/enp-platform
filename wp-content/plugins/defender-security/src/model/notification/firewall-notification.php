<?php

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

	public function before_load() {
		$default = array(
			'title'                => __( 'Firewall - Notification', 'wpdef' ),
			'slug'                 => 'firewall-notification',
			'status'               => self::STATUS_DISABLED,
			'description'          => __( 'Get email when a user or IP is locked out for trying to access your login area.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
			'out_house_recipients' => array(),
			'type'                 => 'notification',
			'dry_run'              => false,
			'configs'              => array(
				'login_lockout' => false,
				'nf_lockout'    => false,
				'limit'         => false,
				'threshold'     => 3,
				'cool_off'      => 24,
			),
		);
		$this->import( $default );
	}

	/**
	 * @param Lockout_Log $model
	 *
	 * @return bool
	 */
	public function check_options( Lockout_Log $model ) {
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
	 */
	public function send( Lockout_Log $model ) {
		if (
			true === filter_var( $this->configs['login_lockout'], FILTER_VALIDATE_BOOLEAN )
			&& Lockout_Log::AUTH_LOCK === $model->type
		) {
			$template = 'login-lockout';
		} else {
			$template = 'lockout-404';
		}

		foreach ( $this->in_house_recipients as $user ) {
			if ( self::USER_SUBSCRIBED !== $user['status'] ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template );
		}

		foreach ( $this->out_house_recipients as $user ) {
			if ( self::USER_SUBSCRIBED !== $user['status'] ) {
				continue;
			}
			$this->send_to_user( $user['email'], $user['name'], $model, $template );
		}
	}

	/**
	 * @param string      $email
	 * @param string      $name
	 * @param Lockout_Log $model
	 * @param string      $template
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	private function send_to_user( $email, $name, $model, $template ) {
		// Check if this meet the threshold.
		if ( true === $this->configs['limit'] ) {
			$count = Email_Track::count( $this->slug, $email, strtotime( '-' . $this->configs['cool_off'] . ' hours' ), time() );
			if ( $count >= $this->configs['threshold'] ) {
				// No send.
				return;
			}
		}
		if ( 'login-lockout' === $template ) {
			$subject  = sprintf( __( 'Login lockout alert for %s', 'wpdef' ), network_site_url() );// phpcs:ignore
			$settings = wd_di()->get( Login_Lockout::class );
			$text     = __( 'We\'ve just locked out the host <strong>%1$s</strong> from %2$s due to more than <strong>%3$s</strong> failed login attempts. %4$s', 'wpdef' );// phpcs:ignore
			if ( 'permanent' === $settings->lockout_type ) {
				$text = sprintf( $text, $model->ip, network_site_url(), $settings->attempt, __( 'They have been banned permanently.', 'wpdef' ) );// phpcs:ignore
			} else {
				$string = sprintf( __( 'They have been locked out for <strong>%1$s %2$s.</strong>', 'wpdef' ), $settings->duration, $settings->duration_unit );// phpcs:ignore
				$text   = sprintf( $text, $model->ip, network_site_url(), $settings->attempt, $string );
			}
		} else {
			$subject  = sprintf( __( '404 lockout alert for %s', 'wpdef' ), network_site_url() );// phpcs:ignore
			$settings = wd_di()->get( Notfound_Lockout::class );
			if ( 'permanent' === $settings->lockout_type ) {
				$text = sprintf(
					__( "We've just locked out the host <strong>%1\$s</strong> from %2\$s due to more than <strong>%3\$s</strong> 404 requests for the file <strong>%4\$s</strong>. They have been banned permanently.", 'wpdef' ),
					$model->ip,
					network_site_url(),
					$settings->attempt,
					$model->tried,
					$settings->duration,
					$settings->duration_unit
				);
			} else {
				$text = sprintf(
					__( "We've just locked out the host <strong>%1\$s</strong> from %2\$s due to more than <strong>%3\$s</strong> 404 requests for the file <strong>%4\$s</strong>. They have been locked out for <strong>%5\$s %6\$s.</strong>", 'wpdef' ),
					$model->ip,
					network_site_url(),
					$settings->attempt,
					$model->tried,
					$settings->duration,
					$settings->duration_unit
				);
			}
		}
		$logs_url = network_admin_url( 'admin.php?page=wdf-ip-lockout&view=logs' );
		$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );
		$content  = wd_di()->get( Firewall::class )->render_partial(
			'email/' . $template,
			array(
				'name'     => $name,
				'text'     => $text,
				'logs_url' => $logs_url,
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
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'notification'               => __( 'Firewall - Notification', 'wpdef' ),
			'login_lockout_notification' => __( 'Login Protection Lockout', 'wpdef' ),
			'ip_lockout_notification'    => __( '404 Detection Lockout', 'wpdef' ),
			'notification_subscribers'   => __( 'Recipients', 'wpdef' ),
			'cooldown_enabled'           => __( 'Limit email notifications for repeat lockouts', 'wpdef' ),
			'cooldown_number_lockout'    => __( 'Repeat Lockouts Threshold', 'wpdef' ),
			'cooldown_period'            => __( 'Repeat Lockouts Period', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
