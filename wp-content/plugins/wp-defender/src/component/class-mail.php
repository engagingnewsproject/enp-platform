<?php
/**
 * Handles the generation of email headers and the retrieval of sender names based on notification slugs.
 *
 * @package WP_Defender\Component
 * @since      4.5.0
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Integrations\Dashboard_Whitelabel;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Firewall_Notification;

/**
 * Handles email functionalities for WP Defender.
 */
class Mail extends Component {

	/**
	 * Get sender name.
	 *
	 * @param  string $notification_slug  The slug of the notification.
	 *
	 * @return string The sender's name.
	 */
	public function get_sender_name( $notification_slug ): string {
		$whitelabel = new Dashboard_Whitelabel();
		if ( $whitelabel->can_whitelabel() ) {
			$plugin_label = $whitelabel->get_plugin_name( Config_Hub_Helper::WDP_ID );
			if ( empty( $plugin_label ) ) {
				$plugin_label = $this->find_feature_name_by_slug( $notification_slug );
			}
		} else {
			$plugin_label = esc_html__( 'Defender', 'wpdef' );
		}

		return $plugin_label;
	}

	/**
	 * Finds the feature name associated with a given notification slug.
	 *
	 * @param  string $slug  The notification slug.
	 *
	 * @return string The feature name associated with the slug.
	 */
	protected function find_feature_name_by_slug( $slug ) {
		switch ( $slug ) {
			case Tweak_Reminder::SLUG:
				return esc_html__( 'Recommendations', 'wpdef' );
			case Malware_Notification::SLUG:
			case Malware_Report::SLUG:
				return esc_html__( 'Malware Scanning', 'wpdef' );
			case Firewall_Notification::SLUG:
			case Firewall_Report::SLUG:
				return esc_html__( 'Firewall', 'wpdef' );
			case Audit_Report::SLUG:
				return esc_html__( 'Audit Logging', 'wpdef' );
			case 'subscription':
				return esc_html__( 'Subscription', 'wpdef' );
			case 'subscribe_confimed':
				return esc_html__( 'Subscription Confirmed', 'wpdef' );
			case 'unsubscription':
				return esc_html__( 'Unsubscription', 'wpdef' );
			case 'totp':
				return esc_html__( 'Two-Factor Authentication', 'wpdef' );
			case Unlock_Me::SLUG_UNLOCK:
				return Unlock_Me::get_feature_title();
			default:
				return '';
		}
	}

	/**
	 * No reply email header.
	 * Generate no reply email header with HTML UTF-8 support.
	 *
	 * @param  string $from_email  The email address to use in the "From" field.
	 * @param  string $notification_slug  The notification slug used to determine the sender's name.
	 *
	 * @return array An array of headers for the email.
	 */
	public function get_headers( $from_email, $notification_slug = '' ): array {
		$from_label = $this->get_sender_name( $notification_slug );
		$headers    = array(
			'From: ' . $from_label . ' <' . $from_email . '>',
			'Content-Type: text/html; charset=UTF-8',
		);

		return $headers;
	}

	// Todo: move defender_noreply_email() from functions.php.
}