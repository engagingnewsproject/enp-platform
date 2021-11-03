<?php

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Traits\Formats;

class Table_Lockout extends Component {
	use Formats;

	const STATUS_BAN = 'ban', STATUS_NOT_BAN = 'not_ban', STATUS_ALLOWLIST = 'allowlist';
	const SORT_DESC  = 'latest', SORT_ASC = 'oldest', SORT_BY_IP = 'ip', SORT_BY_UA = 'user_agent';
	const LIMIT_20   = '20', LIMIT_50 = '50', LIMIT_100 = '100';

	/**
	 * Get IP status.
	 *
	 * @param string $ip
	 *
	 * @return string|void
	 */
	public function get_ip_status_text( $ip ) {
		$bl_component = new \WP_Defender\Component\Blacklist_Lockout();
		if ( $bl_component->is_ip_whitelisted( $ip ) ) {
			return __( 'Is allowlisted', 'wpdef' );
		}
		if ( $bl_component->is_blacklist( $ip ) ) {
			return __( 'Is blocklisted', 'wpdef' );
		}

		$model = Lockout_Ip::get( $ip );
		if ( ! is_object( $model ) ) {
			return __( 'Not banned', 'wpdef' );
		}

		if ( Lockout_Ip::STATUS_BLOCKED === $model->status ) {
			return __( 'Banned', 'wpdef' );
		} elseif ( Lockout_Ip::STATUS_NORMAL === $model->status ) {
			return __( 'Not banned', 'wpdef' );
		}
	}


	/**
	 * Compare IP status with filter status.
	 * Todo: is the method used or not?
	 *
	 * @param string $ip
	 * @param string $key_status
	 *
	 * @return bool
	 */
	public function ip_has_status_text( $ip, $key_status ) {
		$result = false;
		switch ( $key_status ) {
			case self::STATUS_ALLOWLIST:
				$bl_component = new \WP_Defender\Component\Blacklist_Lockout();
				$result       = $bl_component->is_ip_whitelisted( $ip );
				break;
			case self::STATUS_BAN:
				$model  = Lockout_Ip::get( $ip );
				$result = Lockout_Ip::STATUS_BLOCKED === $model->status;
				break;
			case self::STATUS_NOT_BAN:
				$model  = Lockout_Ip::get( $ip );
				$result = Lockout_Ip::STATUS_NORMAL === $model->status;
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * Get current status of the ip due to allowlist|blocklist data.
	 * Todo: is the method used or not?
	 *
	 * @param string $ip
	 *
	 * @return string
	 */
	public function black_or_white( $ip ) {
		$model = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
		if ( in_array( $ip, $model->get_list( 'allowlist' ), true ) ) {
			return 'allowlist';
		} elseif ( in_array( $ip, $model->get_list( 'blocklist' ), true ) ) {
			return 'blocklist';
		}

		return 'na';
	}

	/**
	 * Get types.
	 *
	 * @return array
	 */
	private function get_types() {
		return array(
			'all'                    => __( 'All', 'wpdef' ),
			Lockout_Log::AUTH_FAIL   => __( 'Failed login attempts', 'wpdef' ),
			Lockout_Log::AUTH_LOCK   => __( 'Login lockout', 'wpdef' ),
			Lockout_Log::ERROR_404   => __( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404 => __( '404 lockout', 'wpdef' ),
			Lockout_Log::LOCKOUT_UA  => __( 'User Agent Lockout', 'wpdef' ),
		);
	}

	/**
	 * Get ban statuses.
	 *
	 * @return array
	 */
	private function ban_status() {
		return array(
			'all'                  => __( 'All', 'wpdef' ),
			self::STATUS_NOT_BAN   => __( 'Not Banned', 'wpdef' ),
			self::STATUS_BAN       => __( 'Banned', 'wpdef' ),
			self::STATUS_ALLOWLIST => __( 'Allowlisted', 'wpdef' ),
		);
	}

	/**
	 * Get type.
	 *
	 * @param string $type
	 *
	 * @return mixed|null
	 */
	public function get_type( $type ) {
		$types = array(
			Lockout_Log::AUTH_FAIL        => __( 'Failed login attempts', 'wpdef' ),
			Lockout_Log::AUTH_LOCK        => __( 'Login lockout', 'wpdef' ),
			Lockout_Log::ERROR_404        => __( '404 error', 'wpdef' ),
			Lockout_Log::ERROR_404_IGNORE => __( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404      => __( '404 lockout', 'wpdef' ),
			Lockout_Log::LOCKOUT_UA       => __( 'User Agent Lockout', 'wpdef' ),
		);

		if ( isset( $types[ $type ] ) ) {
			return $types[ $type ];
		}

		return null;
	}

	/**
	 * @return array
	 */
	private function sort_values() {

		return array(
			self::SORT_DESC  => __( 'Latest', 'wpdef' ),
			self::SORT_ASC   => __( 'Oldest', 'wpdef' ),
			self::SORT_BY_IP => __( 'IP Address', 'wpdef' ),
			self::SORT_BY_UA => __( 'User agent', 'wpdef' ),
		);
	}

	/**
	 * @return array
	 */
	private function limit_per_page() {

		return array(
			self::LIMIT_20  => '20',
			self::LIMIT_50  => '50',
			self::LIMIT_100 => '100',
		);
	}

	/**
	 * @return array
	 */
	public function get_filters() {
		return array(
			'lockout_types' => $this->get_types(),
			'ban_status'    => $this->ban_status(),
			'sort_values'   => $this->sort_values(),
			'limit_logs'    => $this->limit_per_page(),
		);
	}
}
