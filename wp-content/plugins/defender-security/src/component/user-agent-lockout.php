<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Lockout_Log;

/**
 * Class User_Agent.
 * Example of User-Agent format:
 * User-Agent: Mozilla/5.0 (<system-information>) <platform> (<platform-details>) <extensions>
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
 *
 * @package WP_Defender\Component
 * @since 2.6.0
*/
class User_Agent extends Component {

	const SCENARIO_USER_AGENT_LOCKOUT = 'user_agent_lockout';
	const REASON_BAD_USER_AGENT = 'bad_user_agent', REASON_BAD_POST = 'bad_post';

	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\User_Agent_Lockout
	 */
	protected $model;

	public function __construct() {
		$this->model = wd_di()->get( \WP_Defender\Model\Setting\User_Agent_Lockout::class );
	}

	/**
	 * Log the event into db, we will use the data in logs page later.
	 *
	 * @param string $ip
	 * @param string $user_agent
	 * @param string $reason
	 */
	private function log_event( $ip, $user_agent, $reason ) {
		$model             = new Lockout_Log();
		$model->ip         = $ip;
		$model->user_agent = $user_agent;
		$model->date       = time();
		$model->tried      = $user_agent;
		$model->blog_id    = get_current_blog_id();
		$model->type       = Lockout_Log::LOCKOUT_UA;
		switch ( $reason ) {
			case self::REASON_BAD_POST:
				// Distinguish between different block cases of User agent lockouts.
				$model->tried = self::REASON_BAD_POST;
				$model->log   = __( 'Locked out due to empty User-Agent and Referer headers', 'wpdef' );
				break;
			case self::REASON_BAD_USER_AGENT:
			default:
				$model->tried = $user_agent;
				$model->log   = __( 'Locked out due to attempted login with banned user agent', 'wpdef' );
				break;
		}
		$model->save();
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function extra_cleaning( $string ) {
		$string = str_replace( '$', 'S;', $string );
		$string = str_replace( '{', '!', $string );
		$string = str_replace( 'shell', 'chell', $string );
		$string = str_replace( 'curl', 'kurl', $string );
		$string = str_replace( '<', '&lt;', $string );

		return $string;
	}

	/**
	 * Queue hooks when this class init.
	 */
	public function add_hooks() {}

	public function is_active_component() {
		return $this->model->is_active() && ! is_admin();
	}

	/**
	 * Is the current UA bad?
	 * @param string $user_agent
	 *
	 * @return bool
	 */
	public function is_bad_user_agent( $user_agent ) {
		foreach ( $this->model->get_lockout_list( 'allowlist' ) as $allowlist ) {
			if ( false !== strpos( $user_agent, $allowlist ) ) {
				// No log.
				return false;
			}
		}
		foreach ( $this->model->get_lockout_list( 'blocklist' ) as $blocklist ) {
			if ( false !== strpos( $user_agent, $blocklist ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function get_message() {
		return ! empty( $this->model->message )
			? $this->model->message
			: __( 'You have been blocked from accessing this website.', 'wpdef' );
	}

	/**
	 * Block the current UA.
	 *
	 * @param string $user_agent
	 * @param string $ip
	 * @param string $reason
	 *
	 * @return void
	 */
	public function block_user_agent_or_ip( $user_agent, $ip, $reason ) {
		// @since 2.6.0
		do_action( 'wd_user_agent_before_block', $user_agent, $ip, $reason );
		$this->log_event( $ip, $user_agent, $reason );
		// Todo: when a checkbox for UA lockout is ready in Firewall Notification, then Def can use the 'defender_notify' hook to send email.
		do_action( 'wd_user_agent_lockout', $this->model, self::SCENARIO_USER_AGENT_LOCKOUT );
		// Shouldn't block IP via hook 'wd_blacklist_this_ip', block only when the button 'Ban IP' is clicked.
	}

	/**
	 * Sanitize User Agent.
	 *
	 * @return string
	 */
	public function sanitize_user_agent() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		$user_agent = apply_filters( 'wd_current_user_agent', $_SERVER['HTTP_USER_AGENT'] );
		$user_agent = trim( sanitize_text_field( $user_agent ) );
		$user_agent = strtolower( $user_agent );
		$user_agent = $this->extra_cleaning( $user_agent );

		return $user_agent;
	}

	/**
	 * Is the POST request with blank User-Agent and Referer?
	 * @param string $user_agent
	 *
	 * @return bool
	 */
	public function is_bad_post( $user_agent ) {

		return true === $this->model->empty_headers
			&& 'POST' === $_SERVER['REQUEST_METHOD']
			&& empty( $user_agent )
			&& empty( $_SERVER['HTTP_REFERER'] );
	}
}
