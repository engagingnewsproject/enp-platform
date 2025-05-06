<?php
/**
 * Handles interactions with the database table for notifications.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_User;
use DateTime;
use Exception;
use DateInterval;
use Calotes\Model\Setting;
use WP_Defender\Traits\User;
use WP_Defender\Traits\Formats;
use WP_Defender\Model\Notification\Malware_Report;
use function wp_timezone;

/**
 * Model for the notifications table.
 */
abstract class Notification extends Setting {

	use User;
	use Formats;

	public const STATUS_DISABLED = 'disabled', STATUS_ACTIVE = 'enabled';
	public const USER_SUBSCRIBED = 'subscribed', USER_SUBSCRIBE_WAITING = 'waiting', USER_SUBSCRIBE_CANCELED = 'cancelled', USER_SUBSCRIBE_NA = 'na';

	/**
	 * Notification title.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $title;

	/**
	 * Unique ID for this notification.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $slug;

	/**
	 * Table column for description.
	 *
	 * @var string
	 * @defender_property
	 */
	public $description;

	/**
	 * This is the status of the current notification, can be active or disabled.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $status;

	/**
	 * This is notification or report.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $type;

	/**
	 * Report sending frequency. Only when $type is report.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $frequency;

	/**
	 * Report sending day. Only when $type is report.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $day;

	/**
	 * This is for when user select report as monthly, we will have the day number, instead of text.
	 *
	 * @var int
	 * @sanitize_text_field
	 * @defender_property
	 */
	public $day_n;

	/**
	 * Same as $day.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $time;

	/**
	 * Holding a list of site user ids, so when sending, we send though this list.
	 *
	 * @var array
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $in_house_recipients = array();

	/**
	 * For additional users, this should contain a list of email and name.
	 *
	 * @var array
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $out_house_recipients = array();

	/**
	 * This when we want to run the report/notification without any email sending.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $dry_run = false;

	/**
	 * This is contains the meta settings of this notification.
	 *
	 * @var array
	 * @defender_property
	 */
	public $configs = array();

	/**
	 * Tracking.
	 *
	 * @var int
	 * @defender_property
	 */
	public $last_sent = 0;

	/**
	 * Table column for estimated timestamp.
	 *
	 * @var int
	 * @defender_property
	 */
	public $est_timestamp;

	/**
	 * Return the default user, we will use this if there is no user in the notification.
	 *
	 * @return array
	 */
	protected function get_default_user(): array {
		$user_id = get_current_user_id();

		return array(
			'name'   => $this->get_user_display( $user_id ),
			'id'     => $user_id,
			'email'  => $this->get_current_user_email( $user_id ),
			'role'   => $this->get_current_user_role( $user_id ),
			'avatar' => get_avatar_url( $this->get_current_user_email( $user_id ) ),
			'status' => self::USER_SUBSCRIBED,
		);
	}

	/**
	 * Check if the current moment is right for sending.
	 *
	 * @return bool
	 */
	public function maybe_send() {
		// @since 2.7.0 We can remove 'dry_run'-condition in the next version.
		if ( true === $this->dry_run ) {
			// No send, but need to track as sent, so we can requeue it.
			if ( 'report' === $this->type ) {
				$this->last_sent = $this->est_timestamp;

				if ( $this->get_next_run() instanceof DateTime ) {
					$this->est_timestamp = $this->get_next_run()->getTimestamp();
				}

				$this->save();
			}

			return false;
		}

		if ( ! $this->check_active_status() ) {
			return false;
		}

		if ( 'notification' === $this->type ) {
			return true;
		}

		if ( 0 === $this->last_sent ) {
			return false;
		}

		$now  = new DateTime( 'now', wp_timezone() );
		$time = apply_filters( 'defender_current_time_for_report', $now );
		// Testing.
		if ( defined( 'WP_DEFENDER_TESTING' ) && true === constant( 'WP_DEFENDER_TESTING' ) ) {
			return true;
		}

		return $time->getTimestamp() >= $this->est_timestamp;
	}

	/**
	 * Calculates the next run date based on the current date and the frequency of the notification.
	 *
	 * @return DateTime|false The next run date or false if the notification type is 'notification' or the status is
	 *     not active.
	 * @throws Exception If an error occurs while creating the DateTime objects or modifying the date.
	 */
	public function get_next_run() {
		if ( 'notification' === $this->type ) {
			return false;
		}
		if ( ! $this->check_active_status() ) {
			return false;
		}

		// Create estimate object.
		$est = new DateTime( 'now', wp_timezone() );
		if ( ! empty( $this->last_sent ) ) {
			// set the timestamp of previous.
			$est->setTimestamp( $this->last_sent );
		}

		// Est should be set as the last send. Create now timestamp.
		$now            = new DateTime( 'now', wp_timezone() );
		$interval       = DateInterval::createFromDateString( (string) $est->getOffset() . 'seconds' );
		[ $hour, $min ] = explode( ':', $this->time );
		$hour           = (int) $hour;
		$min            = (int) $min;
		switch ( $this->frequency ) {
			case 'daily':
				// Set the time.
				$est->add( $interval );
				$est->setTime( $hour, $min, 0 );
				// Convert to current timezone.
				while ( $est->getTimestamp() < $now->getTimestamp() ) {
					$est->add( new DateInterval( 'P1D' ) );
					$est->setTime( $hour, $min, 0 );
				}
				break;
			case 'weekly':
				$est->modify( 'this ' . $this->day );
				$est->add( $interval );
				$est->setTime( $hour, $min, 0 );
				while ( $est->getTimestamp() < $now->getTimestamp() ) {
					$est->modify( 'next ' . $this->day );
					$est->setTime( $hour, $min, 0 );
				}
				break;
			case 'monthly':
				// We will need to check if the date is passed today, if not, use this, if yes, then queue for next month.
				$est->setDate( (int) $est->format( 'Y' ), (int) $est->format( 'm' ), 1 );
				if ( 31 === (int) $this->day_n ) {
					$this->day_n = (int) $est->format( 't' );
				}
				$est->add( new DateInterval( 'P' . ( $this->day_n - 1 ) . 'D' ) );
				$est->setTime( $hour, $min, 0 );
				while ( $est->getTimestamp() < $now->getTimestamp() ) {
					// Already over, move to next month.
					$est->modify( 'next month' );
					$est->setTime( $hour, $min, 0 );
				}
				break;
		}

		return $est;
	}

	/**
	 * We have multiple issues where the email keep sending for no reason, this for debugging later.
	 *
	 * @param  string $email  Email address.
	 */
	public function save_log( $email ): void {
		$track            = new Email_Track();
		$track->timestamp = time();
		$track->source    = $this->slug;
		$track->to        = $email;
		$track->save();
	}

	/**
	 * Checks the active status of the notification.
	 *
	 * @return bool Returns true if the notification is active, false otherwise.
	 */
	public function check_active_status(): bool {
		// Exception after migrating Scheduled scanning to Scan settings.
		if ( Malware_Report::SLUG === $this->slug
			&& true === ( new \WP_Defender\Model\Setting\Scan() )->scheduled_scanning
		) {
			return true;
		}

		return self::STATUS_ACTIVE === $this->status;
	}

	/**
	 * This will return the interval at string.
	 *
	 * @return string
	 * @throws Exception Emits Exception in case of an error.
	 */
	public function to_string(): string {
		if ( ! $this->check_active_status() ) {
			return '-';
		}
		$date = new DateTime( 'now', wp_timezone() );
		$date->setTimestamp( $this->est_timestamp );
		switch ( $this->frequency ) {
			case 'daily':
				return sprintf(
					/* translators: 1: Notification sending frequency, 2: Time of a day. */
					esc_html__( '%1$s at %2$s', 'wpdef' ),
					ucfirst( $this->frequency ),
					$date->format( 'h:i A' )
				);
			case 'weekly':
				return sprintf(
					/* translators: 1: Notification sending frequency, 2: Day of the week, 3: Time of a day. */
					esc_html__( '%1$s on %2$s at %3$s', 'wpdef' ),
					ucfirst( $this->frequency ),
					ucfirst( $this->day ),
					$date->format( 'h:i A' )
				);
			case 'monthly':
			default:
				return sprintf(
					/* translators: 1: Notification sending frequency, 2: Day of the month, 3: Time of a day. */
					esc_html__( '%1$s/%2$d, %3$s', 'wpdef' ),
					ucfirst( $this->frequency ),
					$this->day_n,
					$date->format( 'h:i A' )
				);
		}
	}

	/**
	 * Returns the next run date as a string, based on the type of notification and the active status.
	 *
	 * @param  bool $for_hub  Whether the next run date is for the hub. Default is false.
	 *
	 * @return bool|string Returns false if the notification type is 'notification' or the active status is false.
	 *                     Returns the next run date as a string in the format specified by the date_format and
	 *     time_format options. Returns 'Never' if the active status is false.
	 * @throws Exception If an error occurs while creating the DateTime objects or modifying the date.
	 */
	public function get_next_run_as_string( bool $for_hub = false ) {
		if ( 'notification' === $this->type ) {
			return $for_hub ? false : esc_html__( 'Never', 'wpdef' );
		}

		if ( $for_hub ) {
			return $this->check_active_status()
				? $this->persistent_hub_datetime_format( $this->est_timestamp )
				: false;
		} elseif ( $this->check_active_status() ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$date   = new DateTime( 'now', wp_timezone() );
			$date->setTimestamp( (int) $this->est_timestamp );

			return $date->format( $format );
		} else {
			return esc_html__( 'Never', 'wpdef' );
		}
	}

	/**
	 * We still need to validate the out house recipients email.
	 */
	protected function after_validate(): void {
		foreach ( $this->out_house_recipients as $recipient ) {
			$recipient['email'] = trim( $recipient['email'] );
			if ( empty( $recipient['email'] ) ) {
				continue;
			}
			if ( ! filter_var( $recipient['email'], FILTER_VALIDATE_EMAIL ) ) {
				/* translators: %s: Email address of a recipient. */
				$this->errors[] = sprintf( esc_html__( 'Email %s is invalid format', 'wpdef' ), $recipient['email'] );
			}
		}
	}

	/**
	 * Saves the current state of the object.
	 *
	 * @return void
	 */
	public function save(): void {
		if ( empty( $this->last_sent ) ) {
			$this->last_sent = time();
		}
		$next_run = $this->get_next_run();
		if ( is_object( $next_run ) ) {
			$this->est_timestamp = $next_run->getTimestamp();
		}
		parent::save();
	}

	/**
	 * Inject next run to parent function.
	 *
	 * @return array
	 */
	public function export(): array {
		$data = parent::export();

		$data['next_run']        = $this->get_next_run_as_string();
		$data['all_subscribers'] = array_merge( $this->in_house_recipients, $this->out_house_recipients );

		return $data;
	}

	/**
	 * Overrided method to manipulate user details dynamically.
	 */
	protected function after_load(): void {
		$in_house_recipients = array();

		foreach ( $this->in_house_recipients as $recipient ) {
			$id        = $recipient['id'];
			$user_data = get_userdata( $id );

			if ( $user_data instanceof WP_User ) {
				$in_house_recipients[] = array(
					'name'   => $user_data->display_name,
					'id'     => $user_data->ID,
					'email'  => $user_data->user_email,
					'role'   => $this->get_first_user_role( $user_data ),
					'avatar' => get_avatar_url( $id ),
					'status' => $recipient['status'],
				);
			}
		}

		$this->in_house_recipients = $in_house_recipients;
	}
}