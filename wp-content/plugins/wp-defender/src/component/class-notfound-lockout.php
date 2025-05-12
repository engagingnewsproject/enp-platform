<?php
/**
 * Responsible for handling 404 error detections and lockouts.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Traits\Country;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;

/**
 * Handles the detection of 404 errors and manages lockouts based on configured settings.
 */
class Notfound_Lockout extends Component {

	use Country;

	public const SCENARIO_ERROR_404 = 'error_404', SCENARIO_ERROR_404_IGNORE = 'error_404_ignore', SCENARIO_LOCKOUT_404 = '404_lockout';
	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\Notfound_Lockout
	 */
	public $model;

	/**
	 * Constructor for Notfound_Lockout.
	 */
	public function __construct() {
		$this->model = wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class );
	}

	/**
	 * Queue hooks when this class init.
	 */
	public function add_hooks() {
		add_action( 'template_redirect', array( &$this, 'process_404_detect_multiple' ) );
	}

	/**
	 * Check if useragent is looks like from googlebot.
	 *
	 * @param  string $user_agent  The user agent string to check.
	 *
	 * @return bool
	 */
	private function is_google_ua( $user_agent = '' ): bool {
		if ( empty( $user_agent ) ) {
			$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
			if ( empty( $user_agent ) ) {
				return false;
			}
			$user_agent = User_Agent::fast_cleaning( $user_agent );
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			$user_agent = mb_strtolower( $user_agent, 'UTF-8' );
		} else {
			$user_agent = strtolower( $user_agent );
		}

		if ( false !== stristr( $user_agent, 'googlebot' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if IP is from Google, base on https://support.google.com/webmasters/answer/80553?hl=en.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool
	 */
	private function is_google_ip( $ip ): bool {
		$hostname = gethostbyaddr( $ip );
		// Check if this hostname has googlebot or google.com.
		if ( preg_match( '/\.googlebot|google\.com$/i', $hostname ) ) {
			$hosts = gethostbynamel( $hostname );

			if ( ! is_array( $hosts ) ) {
				return false;
			}

			// Check if this match the original ip.
			foreach ( $hosts as $host ) {
				if ( $ip === $host ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if the user agent belongs to Bing.
	 *
	 * @param  string $user_agent  The user agent string to check.
	 *
	 * @return bool Returns true if the user agent is identified as belonging to Bing, false otherwise.
	 */
	private function is_bing_ua( $user_agent = '' ): bool {

		if ( empty( $user_agent ) ) {
			$user_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
			if ( empty( $user_agent ) ) {
				return false;
			}
			$user_agent = User_Agent::fast_cleaning( $user_agent );
		}

		if ( function_exists( 'mb_strtolower' ) ) {
			$user_agent = mb_strtolower( $user_agent, 'UTF-8' );
		} else {
			$user_agent = strtolower( $user_agent );
		}
		// MSN Bot Useragent https://www.bing.com/webmaster/help/which-crawlers-does-bing-use-8c184ec0.
		$msn_ua = 'Bingbot|MSNBot|MSNBot-Media|AdIdxBot|BingPreview';

		if ( preg_match( '/' . $msn_ua . '/i', $user_agent ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if IP is from Bing, base on https://www.bing.com/webmaster/help/how-to-verify-bingbot-3905dc26.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool
	 */
	private function is_bing_ip( $ip ): bool {
		$hostname = gethostbyaddr( $ip );
		if ( preg_match( '/\.msnbot|msn\.com$/i', $hostname ) ) {
			$hosts = gethostbynamel( $hostname );

			if ( ! is_array( $hosts ) ) {
				return false;
			}

			// Check if this match the original ip.
			foreach ( $hosts as $host ) {
				if ( $ip === $host ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Processes 404 detection for a single IP address.
	 *
	 * @param  string $ip  The IP address to process.
	 */
	public function process_404_detect( string $ip ): void {
		// Check if this from Google.
		if ( $this->is_google_ua() && $this->is_google_ip( $ip ) ) {
			return;
		}
		// or bing.
		if ( $this->is_bing_ua() && $this->is_bing_ip( $ip ) ) {
			return;
		}

		$uri = defender_get_data_from_request( 'REQUEST_URI', 's' );
		// Strip encode.
		$uri   = urldecode( $uri );
		$model = Lockout_Ip::get( $ip );
		$model = $this->record_fail_attempt( $ip, $model );

		$ext = pathinfo( $uri, PATHINFO_EXTENSION );
		$ext = trim( $ext );
		// Downfall from match URL to extension.
		foreach ( $this->model->get_lockout_list( 'allowlist' ) as $pattern ) {
			$pattern = preg_quote( $pattern, '/' );
			if ( preg_match( '/' . $pattern . '$/i', $uri ) ) {
				// Whitelisted, just return.
				return;
			}
		}

		foreach ( $this->model->get_lockout_list( 'blocklist' ) as $pattern ) {
			$pattern = preg_quote( $pattern, '/' );
			if ( preg_match( '/' . $pattern . '$/i', $uri ) ) {
				$this->lock( $model, 'blacklist', $uri );
				$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );

				return;
			}
		}

		if ( strlen( $ext ) ) {
			// If ext isn't null.
			foreach ( $this->model->get_lockout_list( 'allowlist' ) as $whitelist_ext ) {
				if ( str_replace( '.', '', strtolower( $whitelist_ext ) ) === $ext ) {
					// Ext is whitelist, log and return.
					$this->log_event( $ip, $uri, self::SCENARIO_ERROR_404_IGNORE );

					return;
				}
			}

			foreach ( $this->model->get_lockout_list( 'blocklist' ) as $blacklist_ext ) {
				if ( str_replace( '.', '', strtolower( $blacklist_ext ) ) === $ext ) {
					// Block it.
					$this->lock( $model, 'blacklist', $uri );
					$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );

					return;
				}
			}
		}

		$this->log_event( $ip, $uri, self::SCENARIO_ERROR_404 );

		// Count the attempt.
		$window = strtotime( '- ' . $this->model->timeframe . ' seconds' );

		$model = $this->check_meta_data( $model );
		// We will get the latest till oldest, limit by attempt.
		$checks = array_slice( $model->meta['nf'], $this->model->attempt * - 1 );

		if ( count( $checks ) < $this->model->attempt ) {
			return;
		}
		// If the last time is larger.
		$check = min( $checks );
		if ( $check >= $window ) {
			// then lock it.
			$this->lock( $model, 'normal', $uri );
			$this->log_event( $ip, $uri, self::SCENARIO_LOCKOUT_404 );
		}
	}

	/**
	 * Locks the IP and updates the model accordingly.
	 *
	 * @param  Lockout_Ip $model  The Lockout_Ip model instance.
	 * @param  string     $scenario  The scenario under which the lock is being applied.
	 * @param  string     $uri  The URI that triggered the lockout.
	 */
	private function lock( Lockout_Ip $model, $scenario = 'normal', $uri = '' ) {
		if ( 'permanent' === $this->model->lockout_type ) {
			$scenario = 'blacklist';
		}

		if ( 'blacklist' === $scenario ) {
			$model->attempt_404 = 0;
			$model->meta['nf']  = array();
		} else {
			$model->status = Lockout_Ip::STATUS_BLOCKED;
			// @since 3.7.0. The lock_time column is used for temporary lockouts.
			$model->lock_time       = time();
			$model->release_time    = strtotime( '+ ' . $this->model->duration . ' ' . $this->model->duration_unit );
			$model->lockout_message = $this->model->lockout_message;
		}

		$model->save();

		if ( 'blacklist' === $scenario ) {
			do_action( 'wd_blacklist_this_ip', $model->ip );
		}

		/**
		 * Action hook triggered when a user is locked due to a 404 lockout.
		 *
		 * @param  Lockout_Ip  $model  The Lockout_Ip object representing the IP address being locked.
		 * @param  string  $scenario  The scenario of the IP lockout ("normal" or "blacklist").
		 * @param  string  $uri  The URI associated with the 404 lockout.
		 *
		 * @since 4.3.0 The `$uri` parameter was added.
		 */
		do_action( 'wd_404_lockout', $model, $scenario, $uri );
	}

	/**
	 * Store the fail attempt of current IP.
	 *
	 * @param  string     $ip  The IP address.
	 * @param  Lockout_Ip $model  The Lockout_Ip model instance.
	 *
	 * @return Lockout_Ip
	 */
	protected function record_fail_attempt( $ip, $model ): Lockout_Ip {
		// Fix warning with a non-numeric value.
		if ( ! is_numeric( $model->attempt_404 ) ) {
			$model->attempt_404 = 1;
		} else {
			++$model->attempt_404;
		}
		$model->ip = $ip;

		$model = $this->check_meta_data( $model );
		// Cache the time here, so it consumes less memory than query the logs.
		$model->meta['nf'][] = time();
		$model->save();

		return $model;
	}

	/**
	 * Ensures the metadata for the model is correctly initialized and formatted.
	 *
	 * @param  Lockout_Ip $model  The model to check and update.
	 *
	 * @return Lockout_Ip The checked and potentially modified model.
	 */
	protected function check_meta_data( &$model ): Lockout_Ip {
		if (
			! isset( $model->meta['nf'] ) ||
			( isset( $model->meta['nf'] ) && ! is_array( $model->meta['nf'] ) )
		) {
			$model->meta['nf'] = array();
		}

		return $model;
	}

	/**
	 * Log the event into db, we will use the data in logs page later.
	 *
	 * @param  string $ip  The IP address involved in the event.
	 * @param  string $uri  The URI that was accessed.
	 * @param  string $scenario  The scenario under which the event is logged.
	 */
	public function log_event( $ip, $uri, $scenario ) {
		$model             = new Lockout_Log();
		$model->ip         = $ip;
		$user_agent        = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
		$model->user_agent = isset( $user_agent ) ? User_Agent::fast_cleaning( $user_agent ) : null;
		$model->date       = time();
		$model->tried      = $uri;
		$model->blog_id    = get_current_blog_id();

		$ip_to_country = $this->ip_to_country( $ip );

		if ( ! empty( $ip_to_country ) && isset( $ip_to_country['iso'] ) ) {
			$model->country_iso_code = $ip_to_country['iso'];
		}

		switch ( $scenario ) {
			case self::SCENARIO_ERROR_404:
				$model->type = Lockout_Log::ERROR_404;
				$model->log  = sprintf(
				/* translators: %s: URI. */
					esc_html__( 'Request for file %s which doesn`t exist', 'wpdef' ),
					$uri
				);
				break;
			case self::SCENARIO_ERROR_404_IGNORE:
				$model->type = Lockout_Log::ERROR_404_IGNORE;
				$model->log  = sprintf(
				/* translators: %s: URI. */
					esc_html__( 'Request for file %s which doesn`t exist', 'wpdef' ),
					$uri
				);
				break;
			case self::SCENARIO_LOCKOUT_404:
			default:
				$model->type = Lockout_Log::LOCKOUT_404;
				$model->log  = sprintf(
				/* translators: %s: URI. */
					esc_html__( 'Lockout occurred:  Too many 404 requests for %s', 'wpdef' ),
					$uri
				);
				break;
		}
		$model->save();
		if ( Lockout_Log::LOCKOUT_404 === $model->type ) {
			do_action( 'defender_notify', 'firewall-notification', $model );
		}
	}

	/**
	 * Process 404 detection for multiple IPs.
	 *
	 * @return void
	 * @since 4.4.2
	 */
	public function process_404_detect_multiple(): void {
		if ( ! is_404() ) {
			return;
		}

		if ( false === $this->model->detect_logged && is_user_logged_in() ) {
			return;
		}

		$service = wd_di()->get( Blacklist_Lockout::class );
		foreach ( $this->get_user_ip() as $ip ) {
			if ( ! $service->is_ip_whitelisted( $ip ) ) {
				$this->process_404_detect( $ip );
			}
		}
	}
}