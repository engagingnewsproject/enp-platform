<?php
/**
 * Handles User-Agent based operations including lockouts and logging for security purposes.
 *
 * @package    WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Traits\Country;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Model\Notification\Firewall_Notification;

/**
 * Handles User-Agent based operations including lockouts and logging for security purposes.
 *
 * @see   https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
 * @since 2.6.0
 */
class User_Agent extends Component {

	use Country;

	public const SCENARIO_USER_AGENT_LOCKOUT = 'user_agent_lockout';
	public const REASON_BAD_USER_AGENT       = 'bad_user_agent', REASON_BAD_POST = 'bad_post';

	/**
	 * Human Readable text denotes user agent header is empty.
	 */
	public const EMPTY_USER_AGENT_TEXT = 'Empty User Agent';

	/**
	 * Use for cache.
	 *
	 * @var User_Agent_Lockout
	 */
	protected $model;

	/**
	 * Lockout IP model instance.
	 *
	 * @var Lockout_Ip
	 */
	protected $lockout_ip_model;

	/**
	 * Initializes the User_Agent component with necessary models.
	 */
	public function __construct() {
		$this->model            = wd_di()->get( User_Agent_Lockout::class );
		$this->lockout_ip_model = wd_di()->get( Lockout_Ip::class );
	}

	/**
	 * Logs a user agent event into the database.
	 *
	 * @param  string $ip  The IP address associated with the event.
	 * @param  string $user_agent  The user agent string associated with the event.
	 * @param  string $reason  The reason for the event.
	 */
	private function log_event( $ip, $user_agent, $reason ) {
		$model             = new Lockout_Log();
		$model->ip         = $ip;
		$model->user_agent = $user_agent;
		$model->date       = time();
		$model->tried      = $user_agent;
		$model->blog_id    = get_current_blog_id();
		$model->type       = Lockout_Log::LOCKOUT_UA;

		$ip_to_country = $this->ip_to_country( $ip );

		if ( ! empty( $ip_to_country ) && isset( $ip_to_country['iso'] ) ) {
			$model->country_iso_code = $ip_to_country['iso'];
		}

		switch ( $reason ) {
			case self::REASON_BAD_POST:
				// Distinguish between different block cases of User agent lockouts.
				$model->tried = self::REASON_BAD_POST;
				$model->log   = esc_html__( 'Locked out due to empty User-Agent and Referer headers', 'wpdef' );
				break;
			case self::REASON_BAD_USER_AGENT:
			default:
				$model->tried = $user_agent;
				$model->log   = esc_html__( 'Locked out due to attempted login with banned user agent', 'wpdef' );
				break;
		}
		$model->save();
		// The 'defender_notify' hook doesn't work, so send notify directly.
		$module = wd_di()->get( Firewall_Notification::class );
		if ( $module->check_options( $model ) ) {
			$module->send( $model );
		}
	}

	/**
	 * Queue hooks when this class init.
	 */
	public function add_hooks() {
	}

	/**
	 * Checks if the User_Agent component is active.
	 *
	 * @return bool Returns true if the component is active, false otherwise.
	 */
	public function is_active_component(): bool {
		return $this->model->is_active() && ! is_admin();
	}

	/**
	 * Determines if the provided user agent is considered bad.
	 *
	 * @param  string $user_agent  The user agent to check.
	 *
	 * @return bool Returns true if the user agent is bad, false otherwise.
	 */
	public function is_bad_user_agent( $user_agent ): bool {
		$allowlist = str_replace( '#', '\#', $this->model->get_lockout_list( 'allowlist' ) );
		$blocklist = str_replace( '#', '\#', $this->model->get_lockout_list( 'blocklist' ) );

		$allowlist_regex_pattern = '#' . implode( '|', $allowlist ) . '#i';
		$blocklist_regex_pattern = '#' . implode( '|', $blocklist ) . '#i';

		$allowlist_match = preg_match( $allowlist_regex_pattern, $user_agent );
		$blocklist_match = preg_match( $blocklist_regex_pattern, $user_agent );

		if ( count( $allowlist ) > 0 && ! empty( $allowlist_match ) ) {
			return false;
		}

		if ( count( $blocklist ) > 0 && ! empty( $blocklist_match ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the message to display for blocked user agents.
	 *
	 * @return string The block message.
	 */
	public function get_message(): string {
		return ! empty( $this->model->message )
			? $this->model->message
			: esc_html__( 'You have been blocked from accessing this website.', 'wpdef' );
	}

	/**
	 * Blocks a user agent or IP and logs the event.
	 *
	 * @param  string $user_agent  The user agent to block.
	 * @param  string $ip  The IP address to block.
	 * @param  string $reason  The reason for blocking.
	 */
	public function block_user_agent_or_ip( $user_agent, $ip, $reason ) {
		// since 2.6.0.
		do_action( 'wd_user_agent_before_block', $user_agent, $ip, $reason );
		$this->log_event( $ip, $user_agent, $reason );
		do_action( 'wd_user_agent_lockout', $this->model, self::SCENARIO_USER_AGENT_LOCKOUT );
		// Shouldn't block IP via hook 'wd_blacklist_this_ip', block only when the button 'Ban IP' is clicked.
	}

	/**
	 * Cleans a user agent string quickly.
	 *
	 * @param  string $user_agent  The user agent string to clean.
	 *
	 * @return string The cleaned user agent string.
	 */
	public static function fast_cleaning( $user_agent ): string {
		return trim( sanitize_text_field( $user_agent ) );
	}

	/**
	 * Sanitize User Agent.
	 *
	 * @return string
	 */
	public function sanitize_user_agent(): string {
		$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
		if ( empty( $user_agent ) ) {
			return '';
		}

		$user_agent = apply_filters( 'wd_current_user_agent', $user_agent );
		$user_agent = self::fast_cleaning( $user_agent );
		$user_agent = strtolower( $user_agent );

		return $user_agent;
	}

	/**
	 * Checks if the POST request has blank User-Agent and Referer headers.
	 *
	 * @param  string $user_agent  The user agent of the request.
	 *
	 * @return bool Returns true if the headers are considered bad, false otherwise.
	 */
	public function is_bad_post( $user_agent ): bool {
		$server = defender_get_data_from_request( null, 's' );

		return true === $this->model->empty_headers
				&& 'POST' === $server['REQUEST_METHOD']
				&& empty( $user_agent )
				&& empty( $server['HTTP_REFERER'] );
	}

	/**
	 * Verifies the format and usability of an import file for User Agent Lockout settings.
	 *
	 * @param  string $file  The file path to verify.
	 *
	 * @return array|bool Returns the data if the file is valid, false otherwise.
	 */
	public function verify_import_file( $file ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$contents = $wp_filesystem->get_contents( $file );
		$lines    = explode( "\n", $contents );
		$data     = array();
		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				continue;
			}
			$line = str_getcsv( $line, ',', '"', '\\' );
			if ( count( $line ) !== 2 ) {
				return false;
			}
			if ( ! in_array( $line[1], array( 'allowlist', 'blocklist' ), true ) ) {
				return false;
			}

			$ua = $line[0];
			$ua = self::fast_cleaning( $ua );

			if ( '' === $ua ) {
				continue;
			}
			$line[0] = $ua;

			$data[] = $line;
		}
		return $data;
	}

	/**
	 * Get human readable user agent log status text.
	 *
	 * @param  string $log_type  Type of the log. Handles on 'ua_lockout'.
	 * @param  string $user_agent  User Agent name.
	 *
	 * @return string Human-readable text if log_type is UA else empty string.
	 */
	public function get_status_text( $log_type, $user_agent ): string {
		if ( Lockout_Log::LOCKOUT_UA !== $log_type ) {
			return '';
		}

		$status_text = self::EMPTY_USER_AGENT_TEXT;

		if ( self::REASON_BAD_POST === $user_agent ) {
			return $status_text;
		}

		$user_agent_key = $this->model->get_access_status( $user_agent );

		if ( ! empty( $user_agent_key[0] ) ) {
			$status_text = $this->lockout_ip_model->get_access_status_text( $user_agent_key[0] );
		}

		return $status_text;
	}

	/**
	 * A list of known bad user agents.
	 *
	 * @return array An array of user agents.
	 */
	public static function get_spam_user_agent_list() {
		return array(
			'AhrefsBot',
			'DotBot',
			'EmailSiphon',
			'HTTrack',
			'MJ12Bot',
			'Nmap',
			'SEMrushBot',
			'sqlmap',
			'ZmEu',
		);
	}
}