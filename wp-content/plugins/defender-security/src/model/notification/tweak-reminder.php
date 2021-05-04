<?php

namespace WP_Defender\Model\Notification;

use Calotes\Helper\Array_Cache;

/**
 * Class Tweak_Reminder
 *
 * @package WP_Defender\Model\Notification
 */
class Tweak_Reminder extends \WP_Defender\Model\Notification {
	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $table = 'wd_security_tweaks_reminder';

	/**
	 * Load the default first before actual value imported from db
	 *
	 * @return string|void
	 */
	protected function before_load() {
		$params = array(
			'slug'                 => 'tweak-reminder',
			'title'                => __( 'Security Recommendations - Notification', 'wpdef' ),
			'status'               => self::STATUS_DISABLED,
			'description'          => __( 'Get email notifications if/when a security recommendation needs fixing.', 'wpdef' ),
			'in_house_recipients'  => array(
				$this->get_default_user(),
			),
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
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function maybe_send() {
		if ( $this->status !== self::STATUS_ACTIVE ) {
			return false;
		}

		$est = new \DateTime( 'now', wp_timezone() );
		$est->setTimestamp( $this->last_sent );
		$now      = new \DateTime( 'now', wp_timezone() );
		$interval = \DateInterval::createFromDateString( (string) $est->getOffset() . 'seconds' );
		$now->add( $interval );

		switch ( $this->configs['reminder'] ) {
			case 'daily':
				$est->add( new \DateInterval( 'P1D' ) );
				break;
			case 'weekly':
				$est->add( new \DateInterval( 'P1W' ) );
				break;
			case 'monthly':
			default:
				$est->add( new \DateInterval( 'P1M' ) );
				break;
		}
		$est->add( $interval );

		if ( $est->getTimestamp() < $now->getTimestamp() ) {
			return true;
		}

		return false;
	}

	public function send() {
		$tweaks = wd_di()->get( \WP_Defender\Model\Setting\Security_Tweaks::class );
		if ( count( $tweaks->issues ) === 0 ) {
			return;
		}
		$arr    = Array_Cache::get( 'tweaks', 'tweaks' );
		$issues = '';
		foreach ( $tweaks->issues as $slug ) {
			if ( isset( $arr[ $slug ] ) ) {
				$issues .= $this->render_issue( $arr[ $slug ] );
			}
		}

		foreach ( $this->in_house_recipients as $recipient ) {
			if ( $recipient['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $recipient['email'], $recipient['name'], $issues );
		}

		foreach ( $this->out_house_recipients as $recipient ) {
			if ( $recipient['status'] !== \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
				continue;
			}
			$this->send_to_user( $recipient['email'], $recipient['name'], $issues );
		}
		$this->last_sent = time();
		$this->save();
	}

	/**
	 * @param $email
	 * @param $name
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function send_to_user( $email, $name, $issues ) {
		$tweaks   = wd_di()->get( \WP_Defender\Model\Setting\Security_Tweaks::class );
		$logs_url = network_admin_url( 'admin.php?page=wdf-hardener' );
		$logs_url = apply_filters( 'report_email_logs_link', $logs_url, $email );

		$template       = wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->render_partial( 'email/tweaks-reminder', [
			'count'    => count( $tweaks->issues ),
			'view_url' => $logs_url,
			'name'     => $name,
			'issues'   => $issues
		], false );
		$subject        = _n( 'Security Recommendation Report for %s. %s recommendation needs attention.',
			'Security Recommendation Report for %s. %s recommendations needs attention.', count( $tweaks->issues ), 'wpdef' );
		$subject        = sprintf( $subject, network_site_url(), count( $tweaks->issues ) );
		$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
		$no_reply_email = apply_filters( 'wd_recommendation_noreply_email', $no_reply_email );
		$headers        = array(
			'From: Defender <' . $no_reply_email . '>',
			'Content-Type: text/html; charset=UTF-8'
		);
		echo $template;
		wp_mail( $email, $subject, $template, $headers );
	}

	/**
	 * @param $issue
	 *
	 * @return string
	 */
	private function render_issue( $issue ) {
		$data  = $issue->to_array();
		$issue = '<tr style="border:none;padding:0;text-align:left;vertical-align:top">
                                                            <td class="wpmudev-table__row--label"
                                                                style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;border-radius:0 0 0 4px;border-top:.5px solid #d8d8d8;color:#333;font-family:\'Open Sans\',Helvetica,Arial,sans-serif;font-size:16px;font-weight:600;hyphens:auto;line-height:20px;margin:0;padding:10px 15px;text-align:left;vertical-align:top;word-wrap:break-word">
                                                                <img class="wpmudev-table__icon"
                                                                     src="' . defender_asset_url( '/assets/email-assets/img/Warning@2x.png' ) . '"
                                                                     alt="Hero Image"
                                                                     style="-ms-interpolation-mode:bicubic;clear:both;display:inline-block;margin-right:10px;max-width:100%;outline:0;text-decoration:none;vertical-align:middle;width:18px">
                                                                ' . $data['title'] . '
                                                                <span style="color: #888888;font-family: \'Open Sans\';padding-left: 32px;font-size: 13px;font-weight:300;letter-spacing: -0.25px;line-height: 22px;display: block">
                                                                    ' . $data['errorReason'] . '
                                                                </span>
                                                            </td>
                                                            <td class="wpmudev-table__row--warning text-right"
                                                                style="-moz-hyphens:auto;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;border-radius:0 0 4px 0;border-top:.5px solid #d8d8d8;color:#FACD25;font-family:\'Open Sans\',Helvetica,Arial,sans-serif;font-size:12px;font-weight:400;hyphens:auto;line-height:20px;margin:0;padding:10px 15px;text-align:right;vertical-align:top;word-wrap:break-word">
                                                            </td>
                                                        </tr>';

		return $issue;
	}

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'notification'        => __( 'Security Recommendations - Notification', 'wpdef' ),
			'notification_repeat' => __( 'Frequency', 'wpdef' ),
			'subscribers'         => __( 'Recipients', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
