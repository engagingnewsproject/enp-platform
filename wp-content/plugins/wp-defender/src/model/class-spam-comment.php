<?php
/**
 * The spam comment model class.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

/**
 * Class Spam_Comment
 *
 * Provides methods to retrieve spam comment IPs from WordPress database.
 */
class Spam_Comment {
	/**
	 * Retrieve aggregated spam comment IPs across all sites in a multisite setup or from a single site.
	 *
	 * @return array Associative array of IP addresses as keys and their spam comment count as values.
	 */
	public static function get_spam_comments_ip(): array {
		global $wpdb;

		$spam_ips = array();

		if ( is_multisite() ) {
			$offset      = 0;
			$limit       = 100;
			$mu_spam_ips = array();
			while ( $blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs} LIMIT {$offset}, {$limit}", ARRAY_A ) ) { // phpcs:ignore
				if ( ! empty( $blogs ) && is_array( $blogs ) ) {
					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog['blog_id'] );

						$mu_spam_ips = self::fetch_manual_spam_comments_ip();

						if ( ! empty( $mu_spam_ips ) && is_array( $mu_spam_ips ) ) {
							foreach ( $mu_spam_ips as $ip => $count ) {
								$spam_ips[ $ip ] = isset( $spam_ips[ $ip ] )
									? $spam_ips[ $ip ] + $count
									: $count;
							}
						}

						restore_current_blog();
					}
				}
				$offset += $limit;
			}
		} else {
			$spam_ips = self::fetch_manual_spam_comments_ip();
		}

		return is_array( $spam_ips ) ? $spam_ips : array();
	}

	/**
	 * Fetches spam comment IPs and their counts from the current WordPress site which are flagged by moderator.
	 *
	 * @return array Associative array of IP addresses as keys and their spam comment count as values.
	 */
	private static function fetch_manual_spam_comments_ip(): array {
		global $wpdb;

		$sql = "SELECT c.comment_author_IP as ip, COUNT(*) as count
			FROM {$wpdb->comments} c
			JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			WHERE c.comment_approved = 'spam'
			AND c.comment_author_IP IS NOT NULL AND c.comment_author_IP != ''
			AND cm.meta_key = '_wp_trash_meta_time'
			AND cm.meta_value >= UNIX_TIMESTAMP(NOW() - INTERVAL 12 HOUR)
			GROUP BY c.comment_author_IP
			ORDER BY count DESC";

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

		$spam_ips = array();
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$spam_ips[ $row['ip'] ] = (int) $row['count'];
			}
		}

		return $spam_ips;
	}
}