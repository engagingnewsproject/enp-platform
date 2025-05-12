<?php
/**
 * The firewall logs class.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Spam_Comment;

/**
 * Class Firewall_Logs
 */
class Firewall_Logs extends Component {

	/**
	 * Fetch compact Firewall logs. Combination of conditions:
	 * 1. Logs for the specified period.
	 * 2. '404_error'-logs with the same IP, the number of which is not less than 20.
	 * 3. Exclude UA-logs that match entries in the blocklist. Only REASON_BAD_POST UA-logs with the same IP.
	 *
	 * @param  int $from  Fetch Logs from this time to current time.
	 *
	 * @return array
	 */
	public function get_compact_logs( int $from ): array {
		global $wpdb;

		$table   = $wpdb->base_prefix . ( new Lockout_Log() )->get_table();
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT IP, type, tried, COUNT(*) AS frequency FROM {$table}" . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				" WHERE `date` >= %s AND (type IN ('auth_fail', '404_error')" .
				" OR (type = 'ua_lockout' AND tried = %s))" .
				' GROUP BY IP, `type`',
				$from,
				\WP_Defender\Component\User_Agent::REASON_BAD_POST
			),
			ARRAY_A
		);

		$logs = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$frequency = (int) $row['frequency'];

				if ( '404_error' === $row['type'] ) {
					$frequency = intdiv( $frequency, 20 );

					if ( $frequency < 1 ) {
						continue;
					}
				}

				$type = '';
				switch ( $row['type'] ) {
					case 'auth_fail':
						$type = 'login';
						break;
					case '404_error':
						$type = 'not_found';
						break;
					case 'ua_lockout':
						$type = 'user_agent';
						break;
					default:
						continue 2;
				}

				$ip = $row['IP'];
				if ( ! isset( $logs[ $ip ] ) ) {
					$logs[ $ip ] = array( 'ip' => $ip );
				}

				$logs[ $ip ]['reason'][ $type ] = $frequency;
			}
		}

		$spam_comments_ip = Spam_Comment::get_spam_comments_ip();
		if ( ! empty( $spam_comments_ip ) ) {
			// Add spam comments IP to the compact log.
			$this->log( $spam_comments_ip, 'spam-comment.log' );

			foreach ( $spam_comments_ip as $ip => $count ) {
				if ( ! isset( $logs[ $ip ] ) ) {
					$logs[ $ip ] = array( 'ip' => $ip );
				}

				$logs[ $ip ]['reason']['spam_comment'] = $count;
			}
		}

		return array_values( $logs );
	}

	/**
	 * Get spam comment logs automatically marked by the Akismet plugin.
	 *
	 * @return array
	 */
	public function get_akismet_auto_spam_comment_logs(): array {
		$logs = array();
		// Retrieve the current list of blocked IPs from the site transient.
		$ips = get_site_transient( \WP_Defender\Controller\Firewall_Logs::AKISMET_BLOCKED_IPS );
		// Ensure the retrieved data is an array; if not, initialize it as an empty array.
		if ( is_array( $ips ) && ! empty( $ips ) ) {
			$this->log( $ips, 'spam-comment.log' );

			foreach ( $ips as $ip => $count ) {
				if ( ! isset( $logs[ $ip ] ) ) {
					$logs[ $ip ] = array( 'ip' => $ip );
				}

				$logs[ $ip ]['reason']['spam_comment'] = $count;
			}
		}

		delete_site_transient( \WP_Defender\Controller\Firewall_Logs::AKISMET_BLOCKED_IPS );

		return array_values( $logs );
	}
}