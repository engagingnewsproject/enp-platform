<?php
/**
 * Handles the functionality related to user ratings and notifications.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Model\Setting\Security_Tweaks;
use WP_Defender\Traits\Defender_Bootstrap;
use WP_Defender\Helper\Analytics\Rate as Rate_Analytics;

/**
 * Handles the functionality related to user ratings and notifications.
 *
 * @since 4.4.0
 */
class Rate extends Component {

	use Defender_Bootstrap;

	/**
	 * URL for leaving a new review on WordPress.org.
	 *
	 * @var string
	 */
	public const URL_PLUGIN_NEW_REVIEW_VCS = 'https://wordpress.org/support/plugin/defender-security/reviews/#new-post';

	/**
	 * Number of completed scans required to trigger a rating prompt.
	 *
	 * @var int
	 */
	public const NUMBER_COMPLETED_SCANS = 3;

	/**
	 * Number of fixed scan issues required to trigger a rating prompt.
	 *
	 * @var int
	 */
	public const NUMBER_FIXED_SCANS = 5;

	/**
	 * Number of UA lockouts required to trigger a rating prompt.
	 *
	 * @var int
	 */
	public const NUMBER_UA_LOCKOUTS = 25;

	/**
	 * Number of IP lockouts required to trigger a rating prompt.
	 *
	 * @var int
	 */
	public const NUMBER_IP_LOCKOUTS = 25;

	/**
	 * Option name for tracking the number of completed scans.
	 *
	 * @var string
	 */
	public const SLUG_COMPLETED_SCANS = 'defender_counter_completed_scans';

	/**
	 * Option name for tracking the number of fixed scan issues.
	 *
	 * @var string
	 */
	public const SLUG_FIXED_SCAN_ISSUES = 'defender_counter_fixed_scan_issues';

	/**
	 * Option name for tracking if the rate button was clicked.
	 *
	 * @var string
	 */
	public const SLUG_FOR_BUTTON_RATE = 'defender_rating_success';

	/**
	 * Option name for tracking if the later button was clicked.
	 *
	 * @var string
	 */
	public const SLUG_FOR_BUTTON_THANKS = 'defender_days_rating_later_dismiss';

	/**
	 * Option name for storing the installation date of the plugin.
	 *
	 * @var string
	 */
	public const SLUG_FREE_INSTALL_DATE = 'defender_free_install_date';

	/**
	 * Option name for storing the date of the postponed notice. This is global for all rating notices.
	 *
	 * @var string
	 */
	public const SLUG_POSTPONED_NOTICE_DATE = 'defender_postponed_notice_date';

	/**
	 * Option name for tracking the number of UA lockouts.
	 *
	 * @var string
	 */
	public const SLUG_UA_LOCKOUTS = 'defender_counter_ua_lockouts';

	/**
	 * Option name for tracking the number of IP lockouts.
	 *
	 * @var string
	 */
	public const SLUG_IP_LOCKOUTS = 'defender_counter_ip_lockouts';

	/**
	 * Retrieves the count of scans from the database.
	 *
	 * @param  string $slug  The option name to retrieve the count from.
	 *
	 * @return int The count of scans.
	 */
	protected static function get_count_scans( $slug ): int {
		$scan_count = get_site_option( $slug, false );

		return empty( $scan_count ) ? 0 : (int) $scan_count;
	}

	/**
	 * Get a button label to rate.
	 *
	 * @return string
	 */
	public static function get_rate_button_title(): string {
		return esc_html__( 'Rate Defender', 'wpdef' );
	}

	/**
	 * Get a button label to postpone.
	 *
	 * @return string
	 */
	public static function get_postpone_button_title(): string {
		return esc_html__( 'Remind me later', 'wpdef' );
	}

	/**
	 * Get a button label to dismiss.
	 *
	 * @return string
	 */
	public static function get_dismiss_button_title(): string {
		return esc_html__( 'I already did', 'wpdef' );
	}

	/**
	 * Count completed scans.
	 */
	public static function run_counter_of_completed_scans(): void {
		$scan_count = self::get_count_scans( self::SLUG_COMPLETED_SCANS );
		if ( $scan_count < self::NUMBER_COMPLETED_SCANS ) {
			update_site_option( self::SLUG_COMPLETED_SCANS, ++$scan_count );
		}
	}

	/**
	 * Count fixed scans.
	 */
	public static function run_counter_of_fixed_scans(): void {
		$scan_count = self::get_count_scans( self::SLUG_FIXED_SCAN_ISSUES );
		if ( $scan_count < self::NUMBER_FIXED_SCANS ) {
			update_site_option( self::SLUG_FIXED_SCAN_ISSUES, ++$scan_count );
		}
	}

	/**
	 * Count UA lockouts.
	 */
	public static function run_counter_of_ua_lockouts(): void {
		$lockout_count = self::get_count_scans( self::SLUG_UA_LOCKOUTS );
		if ( $lockout_count < self::NUMBER_UA_LOCKOUTS ) {
			update_site_option( self::SLUG_UA_LOCKOUTS, ++$lockout_count );
		}
	}

	/**
	 * Count IP lockouts.
	 */
	public static function run_counter_of_ip_lockouts(): void {
		$lockout_count = self::get_count_scans( self::SLUG_IP_LOCKOUTS );
		if ( $lockout_count < self::NUMBER_IP_LOCKOUTS ) {
			update_site_option( self::SLUG_IP_LOCKOUTS, ++$lockout_count );
		}
	}

	/**
	 * Get notice by given slug.
	 *
	 * @param string $notice_slug  Notice slug.
	 *
	 * @return string
	 */
	public static function get_notice_by_slug( string $notice_slug ): string {
		switch ( $notice_slug ) {
			case 'completed_scans':
			default:
				$text = '<p class="notice-header">' . sprintf(
					/* translators: %d - Number of completed scans. */
					esc_html__( '🎉 Nice work — %d scans completed!', 'wpdef' ),
					self::NUMBER_COMPLETED_SCANS
				);
				$text .= '</p><p class="notice-body">';
				$text .= esc_html__(
					'Your site’s files have been thoroughly scanned for file modifications and outdated plugins. Defender’s here to keep your site safe — could you share your experience with others on WordPress.org?',
					'wpdef'
				);
				$text .= '</p>';
				break;

			case 'fixed_scans':
				$text = '<p class="notice-body">' . sprintf(
					/* translators: %d - Number of fixed scans. */
					esc_html__(
						'You`ve successfully resolved %d malware scan issues! We are happy to be a part of helping you secure your site, and we would appreciate it if you dropped us a rating on wp.org to help us spread the word and boost our motivation.',
						'wpdef'
					),
					self::NUMBER_FIXED_SCANS
				);
				$text .= '</p>';
				break;

			case 'resolved_tweaks':
				$tweak_arr    = wd_di()->get( Security_Tweaks::class )->get_tweak_types();
				$total_tweaks = $tweak_arr['count_fixed'] + $tweak_arr['count_ignored'] + $tweak_arr['count_issues'];
				$text         = '<p class="notice-body">' . sprintf(
					/* translators: %d - Total number. */
					esc_html__(
						'You`ve resolved all %d security recommendations - that`s impressive! We are happy to be a part of helping you secure your site, and we would appreciate it if you dropped us a rating on wp.org to help us spread the word and boost our motivation.',
						'wpdef'
					),
					$total_tweaks
				);
				$text .= '</p>';
				break;

			case 'ua_lockouts':
				$text = '<p class="notice-header">' . sprintf(
					/* translators: %d - Number of UA lockouts. */
					esc_html__( '🤖 Defender has blocked %d malicious bots!', 'wpdef' ),
					self::NUMBER_UA_LOCKOUTS
				);
				$text .= '</p><p class="notice-body">';
				$text .= sprintf(
					/* translators: %d - Number of UA lockouts. */
					esc_html__(
						'That’s %1$d fake crawlers and bad bots stopped from accessing your site. Defender’s User Agent lockouts keep unwanted traffic away so your site stays safe. If Defender’s helped protect your site, please share your experience with a review on WordPress.org.',
						'wpdef'
					),
					self::NUMBER_UA_LOCKOUTS
				);
				$text .= '</p>';
				break;

			case 'ip_lockouts':
				$text = '<p class="notice-header">' . sprintf(
					/* translators: %d - Number of IP lockouts. */
					esc_html__( '🚫 Defender has blocked %d suspicious IPs!', 'wpdef' ),
					self::NUMBER_IP_LOCKOUTS
				);
				$text .= '</p><p class="notice-body">';
				$text .= sprintf(
					/* translators: %d - Number of IP lockouts. */
					esc_html__(
						'That’s %1$d potential threats stopped before they could harm your site. Defender’s Firewall is keeping the bad guys and bots out so you can stay focused on running your site. If you’ve found Defender helpful, please share your experience with a review on WordPress.org.',
						'wpdef'
					),
					self::NUMBER_IP_LOCKOUTS
				);
				$text .= '</p>';
				break;

			case '7_days_installed':
				$text  = '<p class="notice-header">' . esc_html__(
					'Enjoying Defender? We’d love to hear your feedback!',
					'wpdef'
				);
				$text .= '</p><p class="notice-body">';
				$text .= esc_html__(
					'You’ve been using Defender for over a week now, and we’d love to hear about your experience! We’ve spent countless hours developing it for you, and your feedback is important to us. We’d really appreciate your rating.',
					'wpdef'
				);
				$text .= '</p>';
				break;
		}

		return $text;
	}

	/**
	 * Get label by given slug. No need to translate a returned text.
	 *
	 * @param string $notice_slug  Notice slug.
	 *
	 * @return string
	 */
	public static function get_label_by_slug( string $notice_slug ): string {
		switch ( $notice_slug ) {
			case 'completed_scans':
				$text = '3 Malware Scan Completed';
				break;
			case 'fixed_scans':
				$text = '5 Malware Issues Fixed';
				break;
			case 'resolved_tweaks':
				$text = 'Recommendation Resolved';
				break;
			case 'ua_lockouts':
				$text = '25 User Agent Lockout';
				break;
			case 'ip_lockouts':
				$text = '25 IP Lockout';
				break;
			case '7_days_installed':
				$text = '7 Days installed';
				break;
			default:
				$text = '';
				break;
		}
		return $text;
	}

	/**
	 * Get label of the current page.
	 *
	 * @return string
	 */
	public static function get_current_page_label(): string {
		switch ( defender_get_current_page() ) {
			case 'wp-defender':
				$text = 'Dashboard';
				break;
			case 'wdf-hardener':
				$text = 'Recommendations';
				break;
			case 'wdf-scan':
				$text = 'Malware Scan';
				break;
			case 'wdf-logging':
				$text = 'Audit Log';
				break;
			case 'wdf-ip-lockout':
				$text = 'Firewall';
				break;
			case 'wdf-2fa':
				$text = '2FA';
				break;
			case 'wdf-advanced-tools':
				$text = 'Tools';
				break;
			case 'wdf-notification':
				$text = 'Notifications';
				break;
			case 'wdf-setting':
				$text = 'Settings';
				break;
			default:
				$text = '';
				break;
		}

		return $text;
	}

	/**
	 * Reset counter of all prompts.
	 */
	public static function reset_counters(): void {
		update_site_option( self::SLUG_COMPLETED_SCANS, 0 );
		update_site_option( self::SLUG_FIXED_SCAN_ISSUES, 0 );
		update_site_option( self::SLUG_UA_LOCKOUTS, 0 );
		update_site_option( self::SLUG_IP_LOCKOUTS, 0 );
	}

	/**
	 * Is the rating notice displayed? The priority list of prompts:
	 * 1) completed malware scans,
	 * 2) fixed scans,
	 * 3) resolved tweaks,
	 * 4) UA lockouts,
	 * 5) IP lockouts,
	 * 6) default one after 7 days installed.
	 * Note: items #1-5 are without waiting for 7 days.
	 *
	 * @return array
	 */
	public static function is_achievement_displayed(): array {
		$res = array(
			'is_displayed' => true,
			'slug'         => '',
		);
		if ( self::was_rate_request() ) {
			$res['is_displayed'] = false;

			return $res;
		}
		if ( ! self::is_required_page() ) {
			$res['is_displayed'] = false;

			return $res;
		}

		// No needed to translate slugs.
		if ( self::get_count_scans( self::SLUG_COMPLETED_SCANS ) >= self::NUMBER_COMPLETED_SCANS ) {
			$res['slug'] = 'completed_scans';
		} elseif ( self::get_count_scans( self::SLUG_FIXED_SCAN_ISSUES ) >= self::NUMBER_FIXED_SCANS ) {
			$res['slug'] = 'fixed_scans';
		} else {
			$tweak_arr    = wd_di()->get( Security_Tweaks::class )->get_tweak_types();
			$total_tweaks = $tweak_arr['count_fixed'] + $tweak_arr['count_ignored'] + $tweak_arr['count_issues'];
			if ( $tweak_arr['count_fixed'] === $total_tweaks ) {
				$res['slug'] = 'resolved_tweaks';
			} elseif ( self::get_count_scans( self::SLUG_UA_LOCKOUTS ) >= self::NUMBER_UA_LOCKOUTS ) {
				$res['slug'] = 'ua_lockouts';
			} elseif ( self::get_count_scans( self::SLUG_IP_LOCKOUTS ) >= self::NUMBER_IP_LOCKOUTS ) {
				$res['slug'] = 'ip_lockouts';
			} elseif ( self::was_postponed_request() ) {
				$res['is_displayed'] = false;

				return $res;
			}
		}
		// If the conditions are met, display the notice about plugin achievements.
		if ( $res['is_displayed'] && '' !== $res['slug'] ) {
			return $res;
		}
		// If no, display the default one.
		$res['slug'] = '7_days_installed';

		return $res;
	}

	/**
	 * Have there already been clicks on the Rate or Dismiss buttons?
	 *
	 * @return bool
	 */
	private static function was_rate_request(): bool {
		if ( get_site_option( self::SLUG_FOR_BUTTON_RATE, (bool) apply_filters( 'wd_display_rating', false ) ) ) {
			return true;
		}
		if ( get_site_option( self::SLUG_FOR_BUTTON_THANKS, (bool) apply_filters( 'wd_dismiss_rating', false ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Have there already been click to postpone the notice?
	 *
	 * @return bool
	 */
	private static function was_postponed_request(): bool {
		$postponed_date = (int) get_site_option( self::SLUG_POSTPONED_NOTICE_DATE, 0 );

		return time() <= strtotime( '+7 days', $postponed_date );
	}

	/**
	 * Is this required page? Excluded pages: WAF, Free Plugins, Onboard.
	 *
	 * @return bool
	 */
	protected static function is_required_page(): bool {
		$arr   = array(
			'wdf-hardener',
			'wdf-scan',
			'wdf-logging',
			'wdf-ip-lockout',
			'wdf-2fa',
			'wdf-advanced-tools',
			'wdf-notification',
			'wdf-setting',
		);
		$_this = new self();
		if ( ! $_this->is_onboarding() ) {
			$arr[] = 'wp-defender';
		}

		return in_array( defender_get_current_page(), $arr, true );
	}
}