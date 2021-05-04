<?php

namespace WP_Defender\Model\Notification;

use Calotes\Helper\Route;
use Calotes\Model\Setting;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\User;

/**
 * Class Notification
 * @package WP_Defender\Model\Notification
 * @deprecated
 */
abstract class Notification extends Setting {
	use User, Formats;

	const SUBSCRIBED = 'subscribed', UNSUBSCRIBE = 'unsubscribe', WAITING_CONFIRM = 'waiting', NA = 'na';

	const STATUS_INACTIVE = 'inactive', STATUS_DISABLED = 'disabled', STATUS_ACTIVE = 'enabled';
	const TYPE_REPORT = 'report', TYPE_NOTIFICATION = 'notification';

	/**
	 * This usually the vue component name
	 * @var string
	 */
	public $slug;
	/**
	 * Store recipients, in array format, this is mostly an export
	 * from existings users
	 * @var array
	 */
	public $subscribers = [];

	/**
	 * This is an addition of subscribers, which doesnt need to have a
	 * account
	 * @var array
	 */
	public $email_inviters = [];

	/**
	 * How this will run, can be daily, bi-weekly, weekly, monthly
	 * @var string
	 */
	public $frequency = 'weekly';
	/**
	 * Time this will run
	 * @var
	 */
	public $time = '0:00';
	/**
	 * Which day of the week
	 * @var
	 */
	public $day = 'sunday';

	/**
	 * Sometime we would like to ignore the subscriber list
	 * @var bool
	 */
	public $wont_send = false;

	/**
	 * If a setup is halfway, we will set this up
	 *
	 * @var bool
	 */
	public $is_unfinished = false;

	/**
	 * Track the last sent time, we will use this for preventing
	 * @var int
	 */
	public $last_sent = false;

	/**
	 * This for prevent the spamming of subscriber inviter email, those will have a cooldown at least 6 hours
	 */
	public $subscribe_invitation_throttle = [];

	/**
	 * Return the status of this notification, this is depend on each module
	 * Scenario:
	 *  Inactivate: This usually belong to report, where user can turn it on and off
	 *  Disabled: This happen when a notification doesn't have subscribers, or unconfig settings
	 *  Enabled: When all things selted
	 *
	 * @return mixed
	 */
	abstract function get_status();

	/**
	 * Get the title of this
	 * @return mixed
	 */
	abstract function get_title();

	/**
	 * Get the description
	 * @return mixed
	 */
	abstract function get_description();

	/**
	 * In case of the module is disable, we will use this
	 * to return the module URL
	 * @return mixed
	 */
	abstract function get_module_url();

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	abstract function maybe_activate( $data );

	/**
	 * The return will be report or notification
	 * @return string
	 */
	abstract function get_type();

	public function before_load() {
		if ( current_user_can( 'manage_options' ) ) {
			$user_id             = get_current_user_id();
			$user_email          = $this->get_current_user_email( $user_id );
			$this->subscribers[] = [
				'name'   => $this->get_user_display( $user_id ),
				'email'  => $user_email,
				'role'   => $this->get_current_user_role( $user_id ),
				'avatar' => get_avatar_url( $user_email ),
				'status' => self::SUBSCRIBED,
				'login'  => wp_get_current_user()->user_login
			];
		}
	}

	/**
	 * @return bool
	 */
	abstract function maybe_send();

	/**
	 * @return bool
	 */
	abstract function send();

	/**
	 * @return array
	 */
	public function get_recipients() {
		return $this->subscribers;
	}

	/**
	 * @return array
	 */
	public function get_email_inviters() {
		return $this->email_inviters;
	}

	/**
	 * This will return a short list of email=>name
	 * @return array
	 */
	public function get_receivers() {
		$receivers = [];
		foreach ( $this->subscribers as $subscriber ) {
			$receivers[ $subscriber['email'] ] = $subscriber['name'];
		}
		foreach ( $this->email_inviters as $inviter ) {
			$receivers[ $inviter['email'] ] = $inviter['name'];
		}

		$receivers = array_filter( $receivers );

		return $receivers;
	}

	/**
	 * Add a recipient to subscribe list, default status is waiting confirm
	 *
	 * @param $name
	 * @param $email
	 */
	public function add_recipient( $name, $email ) {
		$this->subscribers[] = [
			'name'   => $name,
			'email'  => $email,
			'status' => self::WAITING_CONFIRM
		];
		//todo send email for confirm
		$this->save();
		do_action( 'defender_recipient_added', $email, $name );
	}

	/**
	 * @return string
	 */
	public function get_frequency_text() {
		if ( $this->get_type() === self::TYPE_NOTIFICATION ) {
			return '-';
		}
		if ( $this->get_status() !== self::STATUS_ACTIVE ) {
			return '-';
		}
		$text[] = ucfirst( $this->frequency );
//		if ( $this->frequency !== 'daily' ) {
//			$text[] = sprintf( __( 'every %s', 'wpdef' ), ucfirst( $this->day ) );
//		}
		$text[] = $this->time;

		return implode( ', ', $text );
	}

	/**
	 * This will run a check if the current time is correct for this schedule
	 * @return bool
	 */
	public function is_time_come() {
		if ( $this->is_sent_previously() ) {
			//already sent
			return false;
		}
		switch ( $this->frequency ) {
			case 'daily':
				return $this->is_time_correct();
			case 'weekly':
			case 'monthly':
			default:
				return $this->is_day_correct() && $this->is_time_correct();
		}
	}

	/**
	 * Check if the current report already sent in the previous period
	 * @return bool
	 */
	public function is_sent_previously() {
		if ( $this->last_sent === false ) {
			//this never be sent
			return false;
		}

		$time_string = '+1 day';
		if ( 'weekly' === $this->frequency ) {
			$time_string = '+1 week';
		} elseif ( 'monthly' === $this->frequency ) {
			$time_string = '+1 month';
		}

		return time() <= strtotime( $time_string, $this->last_sent );
	}

	/**
	 * Check if current hour & minute match the preset
	 * @return bool
	 */
	protected function is_time_correct() {
		list( $hour, $min ) = explode( ':', $this->time );
		$h_i = $this->get_current_time();
		list( $compare_hour, $compare_min ) = explode( ':', $h_i );

		return intval( $compare_hour ) >= intval( $hour ) && intval( $compare_min ) >= intval( $min );
	}

	/**
	 * Check if the current day is correct day, also still need if the
	 */
	protected function is_day_correct() {
		return strtolower( $this->day ) === $this->get_current_day();
	}

	/**
	 * Override this on test so we can mock
	 * @return string
	 */
	protected function get_current_day() {
		return strtolower( date( 'l' ) );
	}

	/**
	 * Seprate this as a function, so we can mock and test
	 *
	 * @return false|string
	 */
	protected function get_current_time() {
		$timestamp = current_time( 'timestamp' );
		$h_i       = date( 'H:i', $timestamp );

		return $h_i;
	}

	/**
	 * Get the next run time
	 *
	 * @return bool|int
	 * @throws \Exception
	 */
	public function get_next_run() {
		if ( $this->last_sent === false ) {
			return false;
		}
		$datetime = new \DateTime( $this->format_date_time( $this->last_sent ), wp_timezone() );
		$next_run = false;
		if ( $this->get_status() === self::STATUS_DISABLED || $this->get_status() === self::STATUS_INACTIVE ) {
			return $next_run;
		}
		$time_string = 'P1D';
		if ( 'weekly' === $this->frequency ) {
			$time_string = 'P1W';
		} elseif ( 'monthly' === $this->frequency ) {
			$time_string = 'P1M';
		}

		$next_run = $datetime->add( new \DateInterval( $time_string ) );

		return $next_run->getTimestamp();
	}

	/**
	 * @param $subscribers
	 */
	public function send_subscription_confirm_email( $subscribers ) {
		$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
		$headers        = array(
			'From: Defender <' . $no_reply_email . '>',
			'Content-Type: text/html; charset=UTF-8'
		);
		foreach ( $subscribers as $subscriber ) {
			if ( Notification::WAITING_CONFIRM != $subscriber['status'] ) {
				continue;
			}

			$email = $subscriber['email'];
			if ( isset( $this->subscribe_invitation_throttle[ $email ] )
			     && (int) $this->subscribe_invitation_throttle[ $email ] > time() ) {
				//this is already sent for this notification, we wont send again at least 6 hours
				continue;
			}
			list( $endpoints, $nonces ) = Route::export_routes( 'notification' );
			$url = add_query_arg( [
				'action' => $endpoints['confirm_subscribe'],
				'hash'   => hash( 'sha256', $subscriber['email'] . AUTH_SALT ),
				'uid'    => $this->slug
			], admin_url( 'admin-ajax.php' ) );

			//we send email here
			$ret = wp_mail( $subscriber['email'], __( "Subject", 'wpdef' ),
				sprintf( '<a href="%s">%s</a>', $url, __( 'Subscribe it', 'wpdef' ) ), $headers );
			if ( $ret ) {
				//email sent, we log the next time it can run
				$this->subscribe_invitation_throttle[ $subscriber['email'] ] = strtotime( '+6 hours' );
				$this->save();
			}
		}
	}

	/**
	 * Return the data
	 * @return array
	 */
	public function to_array() {
		$data['slug']           = $this->slug;
		$data['frequency_text'] = $this->get_frequency_text();
		$data['status']         = $this->get_status();
		$data['data']           = $this->export();
		$data['title']          = $this->get_title();
		$data['description']    = $this->get_description();
		$data['module_url']     = $this->get_module_url();
		$data['type']           = $this->get_type();

		return $data;
	}
}
