<?php
/**
 * Handles the functionality for unlocking IP addresses that have been locked out due to security policies.
 *
 * @package    WP_Defender\Component
 */

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;
use WP_Defender\Model\Unlockout;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Controller\Firewall;

/**
 * Handles the functionality for unlocking IP addresses that have been locked out due to security policies.
 *
 * @since 4.6.0
 */
class Unlock_Me extends Component {

	/**
	 * Time after which the unlock attempt is considered expired.
	 *
	 * @var int
	 */
	public const EXPIRED_COUNTER_TIME = 5 * MINUTE_IN_SECONDS;

	/**
	 * Slug used for the unlock action.
	 *
	 * @var string
	 */
	public const SLUG_UNLOCK = 'defender_unlock_me';

	/**
	 * Retrieves the time string for when an unlock attempt expires.
	 *
	 * @return string Time string relative to current time.
	 */
	public static function get_expired_time(): string {
		return (string) apply_filters( 'wpdef_firewall_unlockout_expired_time', '-30 minutes' );
	}

	/**
	 * This is a receiver to unlock IP(-s) using email.
	 * It makes no difference whether the user is logged in or not.
	 *
	 * @return bool|void
	 */
	public function maybe_unlock() {
		// Hash values contains an user email, IP.
		$hash  = HTTP::get( 'hash', '' );
		$login = HTTP::get( 'login', '' );
		// Get Unlock ID(-s).
		$string_uid = HTTP::get( 'uid', '' );
		if ( empty( $hash ) || empty( $login ) || empty( $string_uid ) ) {
			return false;
		}

		$user = get_user_by( 'login', $login );
		if ( ! $user ) {
			$this->log(
				'Unlock Me. Incorrect result. Not found user for UID(-s) ' . $string_uid,
				Firewall::FIREWALL_LOG
			);

			return false;
		}

		$user_email = $user->user_email;
		if ( ! hash_equals( $hash, hash( 'sha256', $user_email . AUTH_SALT ) ) ) {
			$this->log( 'Unlock Me. Incorrect result. Invalid hash.', Firewall::FIREWALL_LOG );

			return false;
		}

		$ips = array();
		// Get the line of ID or several IDs for multiple lockouts, and change status(-es) in Unlockout table.
		$limit_time = strtotime( self::get_expired_time() );

		// There are some ID's.
		$arr_uids = explode( '-', $string_uid );
		if ( ! is_array( $arr_uids ) ) {
			$this->log( 'Unlock Me. Incorrect result. Wrong UID(-s).', Firewall::FIREWALL_LOG );

			return false;
		}
		foreach ( $arr_uids as $arr_uid ) {
			$resolved_ip = Unlockout::get_resolved_ip_by( (int) $arr_uid, $user_email, $limit_time );
			if ( 'expired' === $resolved_ip ) {
				return false;
			} elseif ( '' !== $resolved_ip ) {
				// This is not expired result and no empty one.
				$ips[] = $resolved_ip;
			}
		}

		// All is good. IP's were unblocked.
		if ( empty( $ips ) ) {
			return true;
		}
		// Work with IP's.
		$ips      = array_unique( $ips );
		$first_ip = $ips[0];

		// Remove the user IP's from Local Blocklist.
		$bl = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
		foreach ( $ips as $ip ) {
			$bl->remove_from_list( $ip, 'blocklist' );
			$this->log(
				'Unlock Me. Success. IP ' . $ip . ' have been unblocked from the blocklist.',
				Firewall::FIREWALL_LOG
			);
		}

		// Remove IP(s) from Active lockouts.
		if ( count( $ips ) > 1 ) {
			$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED, $ips );
			foreach ( $models as $model ) {
				$model->status = Lockout_Ip::STATUS_NORMAL;
				$model->save();
				$this->log(
					'Unlock Me. Success. IP ' . $ip . ' have been unblocked from Active lockouts.',
					Firewall::FIREWALL_LOG
				);
			}
		} else {
			$ip = Lockout_Ip::get_unlocked_ip_by( $first_ip );
			if ( ! empty( $ip ) ) {
				$this->log(
					'Unlock Me. Success. IP ' . $ip . ' have been unblocked from Active lockouts.',
					Firewall::FIREWALL_LOG
				);
			}
		}

		// Remove IP(s) from Central IP Blocklist.
		$ret = wd_di()->get( IP\Global_IP::class )->remove_from_blocklist( $ips );
		if ( is_wp_error( $ret ) ) {
			$this->log(
				'Unlock Me. Error. IP(s) ' . implode( ',', $ips ) . ' have not been unblocked from Central IP Blocklist.',
				Firewall::FIREWALL_LOG
			);
		} else {
			$this->log(
				'Unlock Me. Success. IP(s) ' . implode( ',', $ips ) . ' have been unblocked from Central IP Blocklist.',
				Firewall::FIREWALL_LOG
			);
		}

		// Remove the old counter.
		delete_transient( $this->check_ip_by_remote_addr( $first_ip ) );
		// Redirect.
		wp_safe_redirect( Mask_Login::maybe_masked_login_url() );
		exit;
	}

	/**
	 * Display the section if:
	 * 1) no empty IP(-s),
	 * 2) depending on the lockout reason.
	 *
	 * @param  string $reason  The reason for the lockout.
	 * @param  array  $ips  The IPs involved in the lockout.
	 *
	 * @return bool
	 */
	public static function is_displayed( string $reason, array $ips ): bool {
		$excluded_reasons = (array) apply_filters(
			'wpdef_firewall_unlockout_excluded_reasons',
			array(
				'country',
				'demo',
			)
		);
		$is_displayed     = ! in_array( $reason, $excluded_reasons, true ) && ! empty( $ips );

		return (bool) apply_filters( 'wpdef_firewall_unlockout_is_displayed', $is_displayed );
	}

	/**
	 * Get the limit of failed attempts.
	 *
	 * @return int
	 */
	public static function get_attempt_limit(): int {
		return (int) apply_filters( 'wpdef_firewall_unlockout_attempt_limit', 5 );
	}

	/**
	 * Creates a URL for unlocking based on email, user login, and UID array.
	 *
	 * @param  string $email  The user's email.
	 * @param  string $user_login  The user's login name.
	 * @param  array  $arr_uids  Array of UIDs.
	 *
	 * @return string The generated URL for unlocking.
	 */
	public static function create_url( $email, $user_login, $arr_uids ): string {
		$string_uids = implode( '-', $arr_uids );

		return add_query_arg(
			array(
				'action' => self::SLUG_UNLOCK,
				// No need IP.
				'hash'   => hash( 'sha256', $email . AUTH_SALT ),
				'login'  => $user_login,
				'uid'    => $string_uids,
			),
			network_site_url()
		);
	}

	/**
	 * Retrieves the title of the Unlock Me feature.
	 *
	 * @return string The feature title.
	 */
	public static function get_feature_title(): string {
		return esc_html__( 'Unlock Me', 'wpdef' );
	}
}